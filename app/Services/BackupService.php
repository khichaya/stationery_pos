<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class BackupService
{
    /**
     * إنشاء نسخة احتياطية حقيقية وإرجاع مسار الملف الناتج.
     * يُستخدم من الزر اليدوي ومن الجدولة التلقائية على حدٍ سواء.
     */
    public function create(string $prefix = 'bayane_backup'): string
    {
        $filename = $prefix . '_' . now()->format('Y_m_d_His') . '.sql';
        $directory = storage_path('app/backups');
        $path = $directory . '/' . $filename;

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $mysqldumpPath = 'mysqldump';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (File::exists('C:\xampp\mysql\bin\mysqldump.exe')) {
                $mysqldumpPath = '"C:\xampp\mysql\bin\mysqldump.exe"';
            }
        }

        $passwordParam = env('DB_PASSWORD') ? '--password=' . env('DB_PASSWORD') : '';

        // --single-transaction: يأخذ لقطة (snapshot) للبيانات عبر InnoDB بدل قفل الجداول بالكامل
        // --skip-lock-tables: يمنع صراحة أي محاولة لقفل الجداول
        // هذا يمنع تعليق باقي الطلبات (مثل فتح الموقع) أثناء تنفيذ النسخ الاحتياطي
        $command = sprintf(
            '%s --user=%s %s --host=%s --single-transaction --skip-lock-tables %s > %s',
            $mysqldumpPath,
            env('DB_USERNAME'),
            $passwordParam,
            env('DB_HOST'),
            env('DB_daTABASE'),
            escapeshellarg($path)
        );

        exec($command);

        // خطة بديلة إن فشل mysqldump (غير موجود في PATH مثلاً): توليد SQL يدوياً عبر DB
        if (!File::exists($path) || File::size($path) == 0) {
            $this->createFallbackDump($path);
        }

        return $path;
    }

    /**
     * توليد ملف SQL يدوياً باستخدام استعلامات DB مباشرة
     * (يُستخدم فقط إذا فشل أمر mysqldump الخارجي).
     */
    protected function createFallbackDump(string $path): void
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = 'Tables_in_' . env('DB_daTABASE');
        $sqlContent = "-- Bayane System Backup (Fallback Dump) \n\n";

        foreach ($tables as $table) {
            $tableName = $table->$dbName;
            $createTable = DB::select("SHOW CREATE TABLE `$tableName`")[0]->{'Create Table'};
            $sqlContent .= "\n\n" . $createTable . ";\n\n";

            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $array = (array) $row;
                $sqlContent .= "INSERT INTO `$tableName` (" . implode(', ', array_keys($array)) . ") VALUES (" . implode(', ', array_map(function ($value) {
                    return is_null($value) ? 'NULL' : DB::getPdo()->quote($value);
                }, array_values($array))) . ");\n";
            }
        }

        File::put($path, $sqlContent);
    }

    /**
     * تسجيل النسخة في جدول backups (إن وُجد الجدول) حتى تظهر في الأرشيف.
     */
    public function logBackup(string $filename, string $status = 'success'): void
    {
        if (\Schema::hasTable('backups')) {
            DB::table('backups')->insert([
                'filename' => $filename,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
