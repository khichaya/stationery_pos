<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_xxxxxx_create_expenses_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // من سحب المصروف
            $table->decimal('amount', 12, 2);
            $table->string('description'); // فطور، شاي، إلخ
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('expenses'); }
};