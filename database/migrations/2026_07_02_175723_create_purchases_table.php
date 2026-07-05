<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// database/migrations/xxxx_xx_xx_xxxxxx_create_purchases_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('user_id')->constrained('users'); // العون الذي أدخل الوصل
            $table->string('receipt_number')->nullable()->index();
            $table->decimal('total_amount', 12, 2);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('purchases'); }
};