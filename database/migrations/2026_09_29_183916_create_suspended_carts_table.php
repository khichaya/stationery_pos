<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('suspended_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable();
            $table->json('cart_data');
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->string('payment_method')->default('full');
            $table->decimal('paid_amount', 8, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('suspended_carts');
    }
};