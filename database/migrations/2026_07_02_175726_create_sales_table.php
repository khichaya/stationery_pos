<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// database/migrations/xxxx_xx_xx_xxxxxx_create_sales_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users'); // البائع
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0); // المبلغ المدفوع كاش
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, debt, mixed
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sales'); }
};