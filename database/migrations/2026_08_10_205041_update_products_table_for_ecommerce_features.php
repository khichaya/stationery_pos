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
        // 1. تعديل جدول المنتجات لإضافة الحقول الناقصة
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->nullable()->after('barcode');
            }
            if (!Schema::hasColumn('products', 'type')) {
                $table->string('type')->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'material')) {
                $table->string('material')->nullable()->after('type');
            }
            if (!Schema::hasColumn('products', 'images')) {
                $table->json('images')->nullable()->after('image');
            }
            if (!Schema::hasColumn('products', 'colors')) {
                $table->json('colors')->nullable()->after('images');
            }
            if (!Schema::hasColumn('products', 'sizes')) {
                $table->json('sizes')->nullable()->after('colors');
            }
            if (!Schema::hasColumn('products', 'size_guide')) {
                $table->json('size_guide')->nullable()->after('sizes');
            }
            if (!Schema::hasColumn('products', 'rating')) {
                $table->decimal('rating', 2, 1)->default(0.0)->after('min_stock_alert');
            }
            if (!Schema::hasColumn('products', 'reviews_count')) {
                $table->integer('reviews_count')->default(0)->after('rating');
            }
            if (!Schema::hasColumn('products', 'sold_count')) {
                $table->integer('sold_count')->default(0)->after('reviews_count');
            }
        });

        // 2. إنشاء جدول التقييمات (Reviews)
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('customer_name')->nullable(); // اسم الزبون إن لم يكن مسجلاً
                $table->tinyInteger('rating')->default(5); // التقييم من 1 إلى 5
                $table->text('comment')->nullable(); // نص التقييم
                $table->string('selected_color')->nullable(); // اللون الذي اشتراه الزبون
                $table->string('selected_size')->nullable(); // المقاس الذي اشتراه الزبون
                $table->boolean('is_approved')->default(true); // هل التقييم مرئي للعموم
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف جدول التقييمات
        Schema::dropIfExists('reviews');

        // حذف الحقول المضافة من جدول المنتجات
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = ['sku', 'type', 'material', 'images', 'colors', 'sizes', 'size_guide', 'rating', 'reviews_count', 'sold_count'];
            
            // فحص الحقول قبل الحذف لتجنب الأخطاء
            $existingColumns = array_intersect($columnsToDrop, Schema::getColumnListing('products'));
            
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};