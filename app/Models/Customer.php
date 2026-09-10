<?php

namespace App\Models;

use Illuminate\database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'total_debt', 'observation'];

    // علاقة جلب أسطر الديون المفصلة
    public function debts()
    {
        return $this->hasMany(CustomerDebt::class)->orderBy('created_at', 'desc');
    }
}