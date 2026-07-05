<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. إسقاط قيد العلاقة الخارجي أولاً ثم حذف العمود بأمان لمنع خطأ 1553
            if (Schema::hasColumn('users', 'role_id')) {
                // نمرر اسم القيد الافتراضي في لارافل لفك الارتباط
                $table->dropForeign(['role_id']); 
                $table->dropColumn('role_id');
            }

            // 2. إضافة حقل الدور كـ string بعد الـ email
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('staff')->after('email');
            }

            // 3. إضافة حقل الصلاحيات كـ JSON
            if (!Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions']);
            $table->unsignedBigInteger('role_id')->nullable();
            
            // إعادة بناء العلاقة في حال التراجع
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }
};