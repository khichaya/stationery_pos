<?php
// app/Models/PurchaseItem.php
namespace App\Models;

use Illuminate\database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $guarded = [];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function product() { return $this->belongsTo(Product::class); }

    protected static function booted()
    {
        // عند إدخال وصل ممول جديد
        static::created(function ($purchaseItem) {
            $product = $purchaseItem->product;
            
            if (!$product->is_service) {
                // 1. زيادة المخزون
                $product->increment('current_stock', $purchaseItem->quantity);
                
                // 2. تسجيل حركة دخول للمخزن
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => 'purchase',
                    'quantity' => $purchaseItem->quantity // بالموجب
                ]);
            }
        });

        // عند حذف وصل مشتريات (اكتشاف خطأ في الإدخال مثلاً)
        static::deleted(function ($purchaseItem) {
            $product = $purchaseItem->product;
            if (!$product->is_service) {
                // نخصم الكمية التي أدخلناها بالخطأ
                $product->decrement('current_stock', $purchaseItem->quantity);
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => 'return_supplier',
                    'quantity' => -$purchaseItem->quantity
                ]);
            }
        });
    }
}