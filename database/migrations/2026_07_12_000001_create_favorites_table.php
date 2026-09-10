<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // اسم السلعة المفضلة
            $table->decimal('price', 12, 2);     // السعر
            $table->string('icon')->nullable();  // ايقونة/ايموجي اختياري
            $table->string('color')->default('#872061'); // لون البطاقة
            $table->integer('sort_order')->default(0);   // ترتيب العرض
            $table->boolean('is_active')->default(true); // تفعيل/تعطيل
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
