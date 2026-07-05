<?php

use App\Services\BackupService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithFileUploads;

    public $auto_backup = true;
    public $backup_time = '23:00';
    public $backup_frequency = 'daily';
    public $backup_destination = 'local';
    public $backup_file;

    public function mount()
    {
        if (!auth()->user() || !in_array('database_backup', json_decode(auth()->user()->permissions, true) ?? [])) {
    abort(403, 'عذراً، لا تملك الصلاحية الإدارية للوصول لهذه الشاشة بقفل بيان.');
}
        // تحميل الإعدادات المحفوظة مسبقاً عند فتح الصفحة
        $row = DB::table('settings')->where('key', 'backup_config')->first();
        if ($row) {
            $config = json_decode($row->value, true);
            $this->auto_backup = $config['auto_backup'] ?? true;
            $this->backup_time = $config['backup_time'] ?? '23:00';
            $this->backup_frequency = $config['backup_frequency'] ?? 'daily';
            $this->backup_destination = $config['backup_destination'] ?? 'local';
        }
    }

    public function getRealBackups()
    {
        $directory = storage_path('app/backups');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $files = File::files($directory);

        return collect($files)->map(function ($file) {
            return [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                'date' => date('Y-m-d H:i', $file->getMTime()),
            ];
        })->sortByDesc('date');
    }

    // 1️⃣ النسخ اليدوي والتحميل الفوري (يستخدم نفس BackupService)
    public function runManualBackup(BackupService $backupService)
    {
        $path = $backupService->create('bayane_backup');
        $backupService->logBackup(basename($path), 'success');

        session()->flash('success', "📦 تم حفظ نسخة احتياطية حقيقية في السيرفر وتحديث الجدول والتحميل فوراً!");
        return response()->download($path);
    }

    // 2️⃣ حفظ إعدادات الجدولة فقط - لا تشغيل بايثون، لا proc_open، لا exec
    // التنفيذ الفعلي يتم عبر Laravel Scheduler (backup:check-schedule) الذي يعمل كل دقيقة
    public function saveAutoBackupSettings()
    {
        $configValues = [
            'auto_backup' => $this->auto_backup,
            'backup_time' => $this->backup_time,
            'backup_frequency' => $this->backup_frequency,
            'backup_destination' => $this->backup_destination,
        ];

        DB::table('settings')->updateOrInsert(
            ['key' => 'backup_config'],
            ['value' => json_encode($configValues), 'updated_at' => now()]
        );

        session()->flash('success', '⚙️ تم حفظ إعدادات الجدولة بنجاح. سيتم تنفيذ النسخ تلقائياً حسب التوقيت المحدد طالما أن مجدول لارافل يعمل.');
    }

    // 3️⃣ استرجاع قاعدة البيانات
    public function restoreBackup($fileName = null)
    {
        if ($this->backup_file && is_null($fileName)) {
            $this->validate(['backup_file' => 'required|file|max:51200']);
            $path = $this->backup_file->getRealPath();
        } elseif (!is_null($fileName)) {
            $path = storage_path('app/backups/' . $fileName);
            if (!File::exists($path)) {
                session()->flash('error', '❌ ملف النسخة الاحتياطية غير موجود على السيرفر!');
                return;
            }
        } else {
            session()->flash('error', '❌ يرجى تحديد ملف للاسترجاع أولاً!');
            return;
        }

        $mysqlPath = 'mysql';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (File::exists('C:\xampp\mysql\bin\mysql.exe')) {
                $mysqlPath = '"C:\xampp\mysql\bin\mysql.exe"';
            }
        }

        $passwordParam = env('DB_PASSWORD') ? '--password=' . env('DB_PASSWORD') : '';

        $command = sprintf(
            '%s --user=%s %s --host=%s %s < %s',
            $mysqlPath,
            env('DB_USERNAME'),
            $passwordParam,
            env('DB_HOST'),
            env('DB_daTABASE'),
            escapeshellarg($path)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                $sqlContent = File::get($path);
                $queries = array_filter(array_map('trim', explode(";\n", $sqlContent)));

                foreach ($queries as $query) {
                    if (!empty($query) && strpos($query, '--') !== 0) {
                        DB::unprepared($query);
                    }
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                $this->reset('backup_file');
                session()->flash('success', "🔄 نجح الاسترجاع البرمجي! تم إحياء قاعدة البيانات بنجاح وعاد النظام للعمل.");
                return;
            } catch (\Exception $e) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                session()->flash('error', '❌ فشل الاسترجاع البرمجي: ' . $e->getMessage());
                return;
            }
        }

        $this->reset('backup_file');
        session()->flash('success', "🔄 تم استرجاع قاعدة البيانات بنجاح بنسبة 100% وإعادة النظام للحالة السابقة!");
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success p-2 small fw-bold mb-3">✨ {{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger p-2 small fw-bold mb-3">❌ {{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 mb-3">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cpu text-danger me-1"></i> النسخ الاحتياطي الفوري (يدوي)</h6>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted small text-start">أنشئ نسخة احتياطية فورية شاملة لكل الفواتير، المنتجات، والديون الآن للتحميل المباشر.</p>
                    <button wire:click="runManualBackup" wire:loading.attr="disabled" class="btn btn-primary btn-sm w-100 fw-bold py-2 shadow-sm">
                        <span wire:loading.remove><i class="bi bi-cloud-download me-1"></i> إنشاء نسخة وتنزيلها الآن</span>
                        <span wire:loading><i class="bi bi-arrow-clockwise spinning me-1"></i> جاري جرد البيانات والنسخ...</span>
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-success me-1"></i> الجدولة والنسخ الأوتوماتيكي</h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="saveAutoBackupSettings">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" wire:model.live="auto_backup" id="autoBackupSwitch">
                            <label class="form-check-label small fw-bold" for="autoBackupSwitch">تفعيل النسخ الاحتياطي التلقائي</label>
                        </div>

                        @if($auto_backup)
                            <div class="mb-2">
                                <label class="form-label small fw-bold">📅 تكرار العملية</label>
                                <select wire:model.live="backup_frequency" class="form-select form-select-sm">
                                    <option value="daily">يوميًا (كل ليلة)</option>
                                    <option value="weekly">أسبوعيًا (كل جمعة)</option>
                                    <option value="monthly">شهريًا (بداية كل شهر)</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">⏰ وقت التنفيذ المفضل</label>
                                <input type="time" wire:model.live="backup_time" class="form-control form-control-sm text-center font-monospace">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">📍 مكان ومسار الحفظ</label>
                                <select wire:model.live="backup_destination" class="form-select form-select-sm">
                                    <option value="local">📁 تخزين محلي على السيرفر (Local Storage)</option>
                                    <option value="google_drive">☁️ سحابة جوجل درايف (Google Drive)</option>
                                    <option value="dropbox">☁️ سحابة دروب بوكس (Dropbox)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> حفظ إعدادات الأتمتة
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4 mb-3">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-arrow-counterclockwise text-warning me-1"></i> استرجاع قاعدة البيانات (Restore)</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">ارفع ملف احتياطي خارجي بصيغة `.sql` لإعادة النظام للحالة السابقة فوراً.</p>
                    <form wire:submit.prevent="restoreBackup">
                        <div class="input-group input-group-sm">
                            <input type="file" wire:model="backup_file" class="form-control" accept=".sql" required>
                            <button type="submit" class="btn btn-warning fw-bold text-dark px-3 shadow-sm" onclick="return confirm('⚠️ تحذير: استرجاع قاعدة البيانات سيقوم باستبدال الحسابات الحالية! هل أنت متأكد؟')">
                                <i class="bi bi-shield-exclamation me-1"></i> بدء الاسترجاع الفوري
                            </button>
                        </div>
                        @error('backup_file') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-folder2-open text-primary me-1"></i> سجل الأرشيف المتوفر على السيرفر</h6>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>اسم الملف الاحتياطي</th>
                                <th>الحجم</th>
                                <th>النوع</th>
                                <th>تاريخ الحفظ</th>
                                <th>الخيارات الإدارية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->getRealBackups() as $backup)
                                <tr>
                                    <td class="text-start font-monospace fw-bold text-dark p-2">
                                        <i class="bi bi-file-earmark-code text-secondary me-1"></i> {{ $backup['name'] }}
                                    </td>
                                    <td class="font-monospace text-muted">{{ $backup['size'] }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">نظام بيان</span></td>
                                    <td class="text-muted font-monospace">{{ $backup['date'] }}</td>
                                    <td>
                                        <button wire:click="restoreBackup('{{ $backup['name'] }}')" class="btn btn-outline-warning btn-sm px-2 py-0 text-dark fw-bold text-xs" onclick="return confirm('هل أنت متأكد من استرجاع هذا الملف الحقيقي؟')">🔄 استرجاع</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted p-4">📂 مجلد النسخ الاحتياطي فارغ حالياً. اضغط على زر "إنشاء نسخة" لتوليد أول ملف حقيقي.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-xs { font-size: 0.75rem; }
    .spinning { animation: spin 1s linear infinite; display: inline-block; }
    @keyframes spin { 100% { transform: rotate(-360deg); } }
</style>