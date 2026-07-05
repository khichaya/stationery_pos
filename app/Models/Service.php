<?php

namespace App\Models;

use Illuminate\database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['service_type', 'price', 'user_id', 'customer_id', 'payment_method', 'paid_amount'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}