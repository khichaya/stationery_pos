<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->index('stock_movements_product_id_foreign');
            $table->unsignedBigInteger('user_id')->index('stock_movements_user_id_foreign');
            $table->enum('type', ['purchase', 'sale', 'return_customer', 'return_supplier', 'damage', 'adjustment']);
            $table->decimal('quantity', 12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
