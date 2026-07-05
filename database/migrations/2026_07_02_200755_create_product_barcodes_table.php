<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::create('product_barcodes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->onDelete('cascade'); // يربط بالمنتج وحذفه يحذف باركوداته
        $table->string('barcode')->unique(); // الباركود الفرعي الإضافي
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
