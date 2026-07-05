<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_xxxxxx_create_products_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->nullable()->unique()->index(); // فھرس لسرعة قارئ الباركود
            $table->string('name')->index(); // فھرس لسرعة البحث بالاسم
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->onDelete('set null');
            $table->boolean('is_service')->default(false); // خدمات طباعة وبحوث
            $table->integer('package_items_count')->default(1); // عدد القطع داخل العلبة
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('price_1', 12, 2)->default(0); // سعر بيع 1 (تجزئة مثلاً)
            $table->decimal('price_2', 12, 2)->default(0); // سعر بيع 2
            $table->decimal('price_3', 12, 2)->default(0); // سعر بيع 3
            $table->decimal('price_4', 12, 2)->default(0); // سعر بيع 4 (جملة مثلاً)
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0)->index();
            $table->decimal('min_stock_alert', 12, 2)->default(5);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};