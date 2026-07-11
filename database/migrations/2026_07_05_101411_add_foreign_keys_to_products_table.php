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
            $table->foreign(['category_id'])->references(['id'])->on('categories')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['storage_location_id'])->references(['id'])->on('storage_locations')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['supplier_id'])->references(['id'])->on('suppliers')->onUpdate('restrict')->onDelete('set null');
            $table->foreign(['unit_id'])->references(['id'])->on('units')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_category_id_foreign');
            $table->dropForeign('products_storage_location_id_foreign');
            $table->dropForeign('products_supplier_id_foreign');
            $table->dropForeign('products_unit_id_foreign');
        });
    }
};
