<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupService
{
    /**
     * إنشاء نسخة احتياطية حقيقية وإرجاع مسار الملف الناتج.
     * مُصمم للعمل بشكل ممتاز في بيئة Docker بدون الاعتماد على mysqldump.
     */
    public function create(string $prefix = 'bayane_backup'): string
    {
        $filename = $prefix . '_' . now()->format('Y_m_d_His') . '.sql';
        $directory = storage_path('app/backups');
        $path = $directory . '/' . $filename;

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        // في بيئة Docker، نعتمد مباشرة على الدالة البديلة (Fallback)
        // لأن mysqldump غالبا غير متوفرة في حاوية الـ php-fpm
        $this->createFallbackDump($path);

        return $path;
    }

    /**
     * توليد ملف SQL يدوياً باستخدام استعلامات DB مباشرة
     * (تعمل بشكل مثالي في جميع البيئات بما فيها Docker).
     */
    protected function createFallbackDump(string $path): void
    {
        // ✅ جلب كل الجداول
        $tables = DB::select('SHOW TABLES');
        $sqlContent = "-- Bayane System Backup (Docker/Linux Compatible)\n-- Date: " . now() . "\n\n";
        
        // ✅ تعطيل فحص المفاتيح الأجنبية أثناء الاستيراد لتجنب الأخطاء
        $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // ✅ الحل الأكيد: تحويل الكائن إلى مصفوفة وجلب أول عنصر (اسم الجدول)
            $tableArray = (array) $table;
            $tableName = array_values($tableArray)[0];

            // تجاهل الجداول التي لا تحتوي على بيانات أو مخصصة للكاش
            if (in_array($tableName, ['cache', 'sessions', 'jobs', 'failed_jobs'])) {
                continue;
            }

            // 1. جلب هيكل الجدول (Create Table)
            $createTableRes = DB::select("SHOW CREATE TABLE `$tableName`");
            if (!empty($createTableRes)) {
                $createTable = $createTableRes[0]->{'Create Table'};
                $sqlContent .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $sqlContent .= $createTable . ";\n\n";
            }

            // 2. جلب البيانات (Inserts)
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $array = (array) $row;
                
                // تجهيز القيم (تجنب أخطاء الـ NULL)
                $values = array_map(function ($value) {
                    if (is_null($value)) {
                        return 'NULL';
                    }
                    // تنصيص القيم النصية وتأمينها
                    if (is_string($value)) {
                        return DB::getPdo()->quote($value);
                    }
                    return $value;
                }, array_values($array));

                $columns = implode('`, `', array_keys($array));
                $valsStr = implode(', ', $values);

                $sqlContent .= "INSERT INTO `$tableName` (`$columns`) VALUES ($valsStr);\n";
            }
            
            $sqlContent .= "\n";
        }

        $sqlContent .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        // حفظ الملف
        File::put($path, $sqlContent);
    }

    /**
     * تسجيل النسخة في جدول backups (إن وُجد الجدول) حتى تظهر في الأرشيف.
     */
    public function logBackup(string $filename, string $status = 'success'): void
    {
        if (Schema::hasTable('backups')) {
            DB::table('backups')->insert([
                'filename' => $filename,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
