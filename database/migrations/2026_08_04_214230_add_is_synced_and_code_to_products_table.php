<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        if (!Schema::hasColumn('products', 'code')) {
            $table->string('code')->nullable()->unique()->after('id');
        }

        if (!Schema::hasColumn('products', 'is_synced')) {
            $table->boolean('is_synced')->default(false);
        }
    });
}

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_synced', 'code']);
        });
    }
};