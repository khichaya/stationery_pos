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
        Schema::create('product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->longText('content')->nullable(); // محتوى HTML من المحرر
            $table->string('title')->nullable(); // عنوان اختياري للتفاصيل
            $table->boolean('is_published')->default(true); // لعرضه في الموقع أم لا
            $table->timestamps();

            // فهرس للبحث السريع
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};
