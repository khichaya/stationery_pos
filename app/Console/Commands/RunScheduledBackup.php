<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunScheduledBackup extends Command
{
    protected $signature = 'backup:check-schedule';
    protected $description = 'يتحقق كل دقيقة إن كان موعد النسخ الاحتياطي التلقائي قد حان، وينفذه إن لزم';

    public function handle(BackupService $backupService)
    {
        $row = DB::table('settings')->where('key', 'backup_config')->first();

        if (!$row) {
            return; // لا توجد إعدادات محفوظة بعد
        }

        $config = json_decode($row->value, true);

        if (empty($config['auto_backup'])) {
            \Log::info('[backup:check-schedule] auto_backup معطّل، تم التخطي.');
            return;
        }

        $targetTime = $config['backup_time'] ?? '23:00';
        $frequency = $config['backup_frequency'] ?? 'daily';

        $now = now();
        $currentTime = $now->format('H:i');

        if ($currentTime < $targetTime) {
            \Log::info("[backup:check-schedule] لم يحن الوقت بعد. الحالي={$currentTime} المطلوب={$targetTime}");
            return;
        }

        if (!$this->isDueForFrequency($frequency, $now)) {
            \Log::info("[backup:check-schedule] التكرار ({$frequency}) لا يستحق التنفيذ اليوم.");
            return;
        }

        if ($this->alreadyRanToday()) {
            \Log::info('[backup:check-schedule] تم التنفيذ مسبقاً اليوم، تم التخطي.');
            return;
        }

        \Log::info('[backup:check-schedule] الشروط تحققت، جاري تنفيذ النسخ الآن...');

        $path = $backupService->create('bayane_backup_auto');
        $filename = basename($path);

        $success = file_exists($path) && filesize($path) > 0;
        $backupService->logBackup($filename, $success ? 'success' : 'failed');

        $this->markRanToday();

        \Log::info("[backup:check-schedule] انتهى التنفيذ: {$filename} - نجاح={$success}");
        $this->info("تم تنفيذ النسخ الاحتياطي التلقائي: {$filename}");
    }

    protected function isDueForFrequency(string $frequency, $now): bool
    {
        return match ($frequency) {
            'daily' => true,
            'weekly' => $now->isFriday(),
            'monthly' => $now->day === 1,
            default => true,
        };
    }

    protected function alreadyRanToday(): bool
    {
        $row = DB::table('settings')->where('key', 'last_auto_backup_date')->first();
        return $row && $row->value === now()->format('Y-m-d');
    }

    protected function markRanToday(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'last_auto_backup_date'],
            ['value' => now()->format('Y-m-d'), 'updated_at' => now()]
        );
    }
}