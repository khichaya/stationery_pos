<?php

namespace App\Models;

use Illuminate\database\Eloquent\Model;
use Illuminate\database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'phone', 'address', 'company_name'])]
class Supplier extends Model
{
    /**
     * علاقة المورد بالمنتجات (المورد الواحد يمكن أن يمول المكتبة بعدة منتجات)
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}