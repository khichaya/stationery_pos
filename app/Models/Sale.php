<?php

namespace App\Models;

use Illuminate\database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'total_amount',
        'paid_amount',
        'discount_amount',
        'payment_method'
    ];

    // علاقة المبيعات بالزبون
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // علاقة المبيعات بالموظف/المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة الفاتورة بالمواد المباعة داخلها
    public function items()
    {
        return $this->hasMany(SaleItem::class); 
    }
}