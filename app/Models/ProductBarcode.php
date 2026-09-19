<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBarcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'barcode',
    ];

    // العلاقة العكسية مع المنتج
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}