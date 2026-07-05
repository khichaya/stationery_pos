<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();             // اسم المؤسسة (مثال: مكتبة السلام)
            $table->string('manager_name')->nullable();     // اسم المسؤول
            $table->string('phone')->nullable();            // معلومات التواصل (الهاتف)
            $table->string('email')->nullable();            // البريد الإلكتروني
            $table->string('address')->nullable();          // العنوان المادي
            $table->string('nif')->nullable();             // الرقم الجبائي (NIF)
            $table->string('nis')->nullable();             // رقم التعريف الإحصائي (NIS)
            $table->string('rc')->nullable();              // السجل التجاري (RC)
            $table->string('ai')->nullable();              // رقم المادة (Article d'imposition)
            $table->string('invoice_footer')->nullable();   // نص يظهر أسفل الفاتورة (ملاحظة ترحيبية مثلاً)
            $table->string('logo_path')->nullable();        // مسار شعار المؤسسة
            $table->timestamps();
        });

        // حقن سجل افتراضي أولى لكي نقوم بتحديثه مباشرة من الواجهة
        DB::table('institution_settings')->insert([
            'name' => 'مكتبة السلام',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_settings');
    }
};