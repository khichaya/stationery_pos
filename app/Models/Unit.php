<?php

namespace App\Models;

use Illuminate\database\Eloquent\Model;
use Illuminate\database\Eloquent\Attributes\Fillable;

#[Fillable(['name'])]
class Unit extends Model
{
    /**
     * علاقة الوحدة بالمنتجات (الوحدة الواحدة مثل "قطعة" قد تنتمي لعدة منتجات)
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}