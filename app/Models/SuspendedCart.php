<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspendedCart extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'customer_id', 'cart_data', 'discount_amount', 'payment_method', 'paid_amount'];
    
    public function customer() {
        return $this->belongsTo(Customer::class);
    }
}