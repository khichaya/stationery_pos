<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
       public function up(): void
    {
        Schema::create('car_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('brand'); // ماركة السيارة (Toyota)
            $table->string('brand_logo')->nullable(); // مسار شعار الماركة
            $table->string('model'); // موديل السيارة (Hilux)
            $table->string('model_image')->nullable(); // مسار صورة الموديل
            $table->string('years'); // السنوات (2005-2015)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_catalogs');
    }
};
