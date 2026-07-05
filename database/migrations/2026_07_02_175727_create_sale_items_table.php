<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// database/migrations/xxxx_xx_xx_xxxxxx_create_sale_items_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2); // تثبيت السعر وقت البيع
            $table->decimal('subtotal', 12, 2);
            $table->boolean('is_returned')->default(false); // لتتبع حالة الإرجاع
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sale_items'); }
};