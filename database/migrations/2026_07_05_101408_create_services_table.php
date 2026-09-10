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
        Schema::create('services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('service_type');
            $table->decimal('price', 12);
            $table->unsignedBigInteger('user_id')->index('services_user_id_foreign');
            $table->unsignedBigInteger('customer_id')->nullable()->index('services_customer_id_foreign');
            $table->string('payment_method');
            $table->decimal('paid_amount', 12)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
