<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    // تحديد اسم الجدول بشكل صريح (اختياري لكنه ممارسة جيدة)
    protected $table = 'reviews';

    // الحقول التي يُسمح بملئها وتعديلها
    protected $fillable = [
        'product_id',
        'user_id',
        'customer_name',
        'rating',
        'comment',
        'selected_color',
        'selected_size',
        'is_approved',
    ];

    // تحويل أنواع البيانات (Casting)
    protected $casts = [
        'is_approved' => 'boolean',  // يتعامل معه كـ true/false
        'rating' => 'integer',       // يتعامل معه كرقم صحيح
    ];

    // ================= Relationships (العلاقات) =================

    // كل تقييم ينتمي لمنتج واحد
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // كل تقييم ينتمي لمستخدم واحد (إذا كان مسجلاً الدخول)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ================= Local Scopes (نطاقات مخصصة) =================
    
    // دالة مساعدة لجلب التقييمات المعتمدة/الظاهرة فقط
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}