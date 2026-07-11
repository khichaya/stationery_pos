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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_id')->index('purchase_items_purchase_id_foreign');
            $table->unsignedBigInteger('product_id')->index('purchase_items_product_id_foreign');
            $table->decimal('quantity', 12);
            $table->decimal('purchase_price', 12);
            $table->decimal('subtotal', 12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
