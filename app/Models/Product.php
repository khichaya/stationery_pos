<?php
// app/Models/Product.php
namespace App\Models;

use Illuminate\database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    // العلاقات (Relationships)
    public function category() { return $this->belongsTo(Category::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
}