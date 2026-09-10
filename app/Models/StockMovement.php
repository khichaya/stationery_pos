<?php
// app/Models/StockMovement.php
namespace App\Models;

use Illuminate\database\Eloquent\Model;

class StockMovement extends Model
{
    protected $guarded = [];

    public function product() { return $this->belongsTo(Product::class); }
    public function user() { return $this->belongsTo(User::class); }
}