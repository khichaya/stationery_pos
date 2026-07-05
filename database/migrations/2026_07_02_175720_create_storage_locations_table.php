<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_xxxxxx_create_storage_locations_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // الرف أ1، المستودع الرئيسي
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('storage_locations'); }
};