<?php

use Illuminate\database\Migrations\Migration;
use Illuminate\database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_type'); // نوع الخدمة (مثال: كتابة بحث، تنسيق مستندات)
            $table->decimal('price', 12, 2); // قيمة الخدمة
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // من قام بالخدمة (المستخدم الفاتح حسابه)
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null'); // الزبون (اختياري في حال الدين)
            $table->string('payment_method'); // طريقة الدفع (full | partial | debt)
            $table->decimal('paid_amount', 12, 2)->default(0); // المبلغ المدفوع كاش
            $table->timestamps(); // تاريخ الخدمة ووقتها بدقة (created_at)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};