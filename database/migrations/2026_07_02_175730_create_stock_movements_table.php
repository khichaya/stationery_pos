<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// database/migrations/xxxx_xx_xx_xxxxxx_create_stock_movements_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', ['purchase', 'sale', 'return_customer', 'return_supplier', 'damage', 'adjustment']);
            $table->decimal('quantity', 12, 2);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('stock_movements'); }
};