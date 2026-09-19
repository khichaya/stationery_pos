<?php
// app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    // العلاقات (Relationships)
    public function category() { return $this->belongsTo(Category::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
    
    // إضافة علاقة الباركودات المتعددة
    public function barcodes() 
    { 
        return $this->hasMany(ProductBarcode::class); 
    }
}