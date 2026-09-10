<?php
/**
 * سكربت اختبار مستقل تماماً عن Laravel و Livewire
 * الهدف: التأكد هل مشكلة "filename/directory/volume label" سببها proc_open
 * الخاص بتشغيل بايثون، أم أنها آتية من مكان آخر (مثل أوامر mysqldump).
 *
 * طريقة التشغيل:
 *   ضع هذا الملف في جذر المشروع (بجانب artisan) ثم من CMD:
 *   php test_launch.php
 */

echo "========================================\n";
echo " اختبار تشغيل بايثون في الخلفية (منعزل)\n";
echo "========================================\n\n";

// 1. تحديد المسار الحقيقي للسكربت - عدّل هذا المسار حسب مشروعك
$scriptPath = __DIR__ . '/scripts/bayane_backup_service.py';
$scriptPath = realpath($scriptPath);

echo "1) فحص مسار السكربت:\n";
if (!$scriptPath) {
    echo "   ❌ realpath() رجع false. الملف غير موجود في المسار المتوقع.\n";
    echo "   تأكد من المسار: " . __DIR__ . "/scripts/bayane_backup_service.py\n";
    exit(1);
}
echo "   ✅ المسار: $scriptPath\n\n";

// 2. فحص وجود بايثون في PATH
echo "2) فحص أمر 'python' في PATH:\n";
$whereOutput = [];
exec('where python 2>&1', $whereOutput, $whereReturn);
if ($whereReturn !== 0) {
    echo "   ❌ 'python' غير موجود في PATH. جرب 'where py' أو تحقق من التثبيت.\n";
    echo "   الناتج: " . implode("\n", $whereOutput) . "\n";
} else {
    echo "   ✅ تم إيجاد بايثون في:\n     " . implode("\n     ", $whereOutput) . "\n";
}
echo "\n";

// 3. محاولة proc_open بدون أي shell (bypass_shell)
echo "3) محاولة تشغيل proc_open (bypass_shell => true):\n";

$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pythonBinary = $isWindows ? 'python' : 'python3';

$descriptorSpec = $isWindows
    ? [
        0 => ['pipe', 'r'],
        1 => ['file', 'NUL', 'w'],
        2 => ['file', 'NUL', 'w'],
    ]
    : [
        0 => ['pipe', 'r'],
        1 => ['file', '/dev/null', 'w'],
        2 => ['file', '/dev/null', 'w'],
    ];

$options = $isWindows ? ['bypass_shell' => true] : [];

$process = @proc_open(
    [$pythonBinary, $scriptPath],
    $descriptorSpec,
    $pipes,
    __DIR__,
    null,
    $options
);

if (!is_resource($process)) {
    echo "   ❌ proc_open فشل في إرجاع resource صالح.\n";
    $error = error_get_last();
    if ($error) {
        echo "   تفاصيل الخطأ: " . $error['message'] . "\n";
    }
    exit(1);
}

foreach ($pipes as $pipe) {
    if (is_resource($pipe)) {
        fclose($pipe);
    }
}

$status = proc_get_status($process);
echo "   ✅ تم استدعاء proc_open بنجاح.\n";
echo "   PID: " . ($status['pid'] ?? 'N/A') . "\n";
echo "   Running: " . ($status['running'] ? 'true' : 'false') . "\n\n";

echo "4) انتظر 3 ثواني ثم تحقق يدوياً في Task Manager أو بالأمر التالي:\n";
echo "   tasklist | findstr python\n\n";

sleep(3);

$statusAfter = proc_get_status($process);
echo "   الحالة بعد 3 ثواني -> Running: " . ($statusAfter['running'] ? 'true (لا يزال يعمل، جيد)' : 'false (توقف أو انتهى فوراً)') . "\n";

echo "\n========================================\n";
echo " انتهى الاختبار.\n";
echo "========================================\n";
