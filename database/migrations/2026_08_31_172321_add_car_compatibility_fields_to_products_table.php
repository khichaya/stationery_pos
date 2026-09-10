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
        Schema::table('products', function (Blueprint $table) {
            // ✅ إضافة حقول التوافق (الماركة، الموديل، السنوات)
            $table->string('car_brand')->nullable()->after('material');
            $table->string('car_model')->nullable()->after('car_brand');
            $table->string('years')->nullable()->after('car_model');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['car_brand', 'car_model', 'years']);
        });
    }
};
