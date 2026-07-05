<?php
// app/Models/SaleItem.php
namespace App\Models;

use Illuminate\database\Eloquent\Model;

class SaleItem extends Model
{
    protected $guarded = [];

    // العلاقات
    public function sale() { return $this->belongsTo(Sale::class); }
    public function product() { return $this->belongsTo(Product::class); }

    // الأحداث التلقائية (Model Events) لضمان دقة الحسابات 100%
    protected static function booted()
    {
        // الاحتمال الأول: عند إنشاء عملية بيع جديدة
        static::created(function ($saleItem) {
            $product = $saleItem->product;
            
            // نتحقق أولاً أنها ليست خدمة (لأن الخدمات مثل الطباعة لا مخزون لها)
            if ($saleItem->product && !$saleItem->product->is_service) {
                // 1. إنقاص المخزون
                $product->decrement('current_stock', $saleItem->quantity);
                
                // 2. تسجيل حركة خروج من المخزن
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id() ?? 1, // المستخدم الحالي
                    'type' => 'sale',
                    'quantity' => -$saleItem->quantity // بالسالب لأنها خرجت
                ]);
            }
        });

        // الاحتمال الثاني: عند إرجاع السلعة (تحديث السجل)
        static::updated(function ($saleItem) {
            // إذا تم تغيير حقل الإرجاع وأصبح "نعم"
            if ($saleItem->isDirty('is_returned') && $saleItem->is_returned) {
                $product = $saleItem->product;
                
               if ($saleItem->product && !$saleItem->product->is_service) {
                    // 1. إعادة الكمية للمخزون
                    $product->increment('current_stock', $saleItem->quantity);
                    
                    // 2. تسجيل حركة عودة للمخزن
                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id() ?? 1,
                        'type' => 'return_customer',
                        'quantity' => $saleItem->quantity // بالموجب لأنها دخلت
                    ]);
                }
            }
        });

        // الاحتمال الثالث: عند حذف الفاتورة تماماً (إلغاء البيع)
        static::deleted(function ($saleItem) {
            $product = $saleItem->product;
            // نسترجع السلعة للمخزن فقط إذا لم تكن قد أُرجعت سابقاً ولم تكن خدمة
            if (!$product->is_service && !$saleItem->is_returned) {
                $product->increment('current_stock', $saleItem->quantity);
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => 'adjustment', // تعديل بسبب الحذف
                    'quantity' => $saleItem->quantity
                ]);
            }
        });
    }
}