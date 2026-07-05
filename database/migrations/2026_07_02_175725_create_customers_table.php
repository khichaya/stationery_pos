<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_xxxxxx_create_customers_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('phone')->nullable()->index();
            $table->decimal('balance', 12, 2)->default(0); // موجب: دين عليه، سالب: له مال عندنا
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('customers'); }
};