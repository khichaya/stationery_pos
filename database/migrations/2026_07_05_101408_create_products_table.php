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
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('barcode')->nullable()->unique();
            $table->string('box_barcode')->nullable();
            $table->string('name')->index();
            $table->unsignedBigInteger('category_id')->index('products_category_id_foreign');
            $table->unsignedBigInteger('unit_id')->index('products_unit_id_foreign');
            $table->unsignedBigInteger('storage_location_id')->nullable()->index('products_storage_location_id_foreign');
            $table->boolean('is_service')->nullable()->default(false);
            $table->integer('package_items_count')->default(1);
            $table->decimal('purchase_price', 12)->default(0);
            $table->decimal('price_1', 12)->default(0);
            $table->decimal('price_2', 12)->default(0);
            $table->decimal('price_3', 12)->default(0);
            $table->decimal('price_4', 12)->default(0);
            $table->decimal('discount', 12)->default(0);
            $table->decimal('current_stock', 12)->default(0)->index();
            $table->decimal('min_stock_alert', 12)->default(5);
            $table->timestamps();
            $table->unsignedBigInteger('supplier_id')->nullable()->index('products_supplier_id_foreign');
            $table->string('location')->nullable();
            $table->string('image')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
