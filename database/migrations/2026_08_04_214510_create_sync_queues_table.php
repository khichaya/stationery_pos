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
    Schema::create('sync_queues', function (Blueprint $table) {
        $table->id();
        $table->string('action'); // create, update, delete
        $table->string('model_type');
        $table->unsignedBigInteger('model_id')->nullable();
        $table->json('payload'); // بيانات المنتج
        $table->boolean('processed')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_queues');
    }
};
