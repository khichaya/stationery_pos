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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sale_id')->index('sale_items_sale_id_foreign');
            $table->unsignedBigInteger('product_id')->nullable()->index('sale_items_product_id_foreign');
            $table->decimal('quantity', 12);
            $table->decimal('unit_price', 12);
            $table->decimal('subtotal', 12);
            $table->boolean('is_returned')->default(false);
            $table->timestamps();
            $table->string('product_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
