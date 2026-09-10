<?php

namespace App\Models;

use Illuminate\database\Eloquent\Model;

class Expense extends Model
{
    // الحقول الحقيقية المتطابقة مع قاعدة بياناتك الحالية
    protected $fillable = ['user_id', 'amount', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}