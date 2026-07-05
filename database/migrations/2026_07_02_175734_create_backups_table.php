<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_xxxxxx_create_backups_table.php
return new class extends Migration {
    public function up(): void {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('status'); // success, failed
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('backups'); }
};