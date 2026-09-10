<?php

namespace App\Models;

use Illuminate\database\Eloquent\Model;
use Illuminate\database\Eloquent\Attributes\Fillable;

#[Fillable(['name'])]
class Category extends Model
{
    // علاقة الصنف بالمنتجات (الصنف الواحد يحتوي على عدة منتجات)
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}