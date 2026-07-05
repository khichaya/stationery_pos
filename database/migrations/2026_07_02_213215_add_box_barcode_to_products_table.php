<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // إضافة حقل باركود العلبة بعد حقل باركود القطعة
            if (!Schema::hasColumn('products', 'box_barcode')) {
                $table->string('box_barcode')->nullable()->after('barcode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('box_barcode');
        });
    }
};