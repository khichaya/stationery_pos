<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncProductToShopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30;

    protected $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle(): void
    {
        // ✅ استخدام config بدلاً من env لتجنب الفشل بعد config:cache
         $url = 'https://khaled-auto-shop.onrender.com/api/sync-product';
        $token = '1|fBicdgvrUaCbgyEPOQGELsdqApHxAMpj2Zz0cmgi92d01a51';

        // ✅ استخدام blank للتحقق من القيمة الفارغة بأمان
        if (blank($url) || blank($token)) {
            Log::error("Sync Job: WEB_SHOP_URL or WEB_SHOP_TOKEN is not set in config.");
            return;
        }

        $this->product->load(['details', 'category', 'unit']);

        $colors = is_string($this->product->colors) ? json_decode($this->product->colors, true) : $this->product->colors;
        $compatibility = is_string($this->product->compatibility) ? json_decode($this->product->compatibility, true) : $this->product->compatibility;
        $galleryImages = is_string($this->product->images) ? json_decode($this->product->images, true) : $this->product->images;

        $supplierName = null;
        if ($this->product->supplier_id) {
            $supplier = Supplier::find($this->product->supplier_id);
            if ($supplier) {
                $supplierName = $supplier->name;
            }
        }

        $data = [
            'code'           => $this->product->code ?? ('PROD-' . $this->product->id),
            'name'           => $this->product->name,
            'sku'            => $this->product->sku,
            'barcode'        => $this->product->barcode,
            'type'           => $this->product->type,
            'material'       => $this->product->material,
            'car_brand'       => $this->product->car_brand,
            'car_model'       => $this->product->car_model,
            'years'           => $this->product->years,
            'price_1'        => $this->product->price_1 ?? 0,
            'price_2'        => $this->product->price_2 ?? 0,
            'price_3'        => $this->product->price_3 ?? 0,
            'price_4'        => $this->product->price_4 ?? 0,
            'current_stock'  => $this->product->current_stock ?? 0,
            'colors'         => is_array($colors) ? implode(', ', $colors) : $colors,
            'compatibility'  => is_array($compatibility) ? implode(', ', $compatibility) : $compatibility,
            'category_name'  => optional($this->product->category)->name ?? 'Général',
            'unit_name'      => optional($this->product->unit)->name,
            'supplier_name'  => $supplierName,
        ];

        if ($this->product->details) {
            $data['details_title']      = $this->product->details->title;
            $data['details_content']    = $this->product->details->content;
            $data['details_published']  = $this->product->details->is_published ? 1 : 0;
        }

         $request = Http::timeout(30)->withToken($token)->withoutVerifying();

        // ✅ استخدام fopen (Stream) لتقليل استهلاك الذاكرة عند رفع الصور
        $mainImgPath = $this->product->image;
        if ($mainImgPath && Storage::disk('public')->exists($mainImgPath)) {
            $absolutePath = Storage::disk('public')->path($mainImgPath);
            $request = $request->attach(
                'image_file', 
                fopen($absolutePath, 'r'), 
                basename($mainImgPath)
            );
        }

        if (!empty($galleryImages) && is_array($galleryImages)) {
            foreach ($galleryImages as $index => $galleryImgPath) {
                if (Storage::disk('public')->exists($galleryImgPath)) {
                    $absoluteGalleryPath = Storage::disk('public')->path($galleryImgPath);
                    $request = $request->attach(
                        "gallery_images[{$index}]", 
                        fopen($absoluteGalleryPath, 'r'), 
                        basename($galleryImgPath)
                    );
                }
            }
        }

        // إرسال الطلب
        // (المتجر السحابي يعمل updateOrCreate بناءً على code، لذا فهو آمن ضد التكرار في حال الـ retry)
        $response = $request->post($url, $data);
        Log::info("Shop Reply: " . $response->body());
        if (!$response->successful()) {
            Log::error("Sync failed for Product ID {$this->product->id}: " . $response->status() . " - " . $response->body());
            throw new \Exception("Sync failed for Product ID {$this->product->id}: " . $response->body());
        }

        // تحديث مباشر في قاعدة البيانات لتخطي الـ Observers ومنع التكرار اللانهائي
        \App\Models\Product::where('id', $this->product->id)->update(['is_synced' => true]);
        Log::info("Product ID {$this->product->id} synced successfully to shop.");
    }

    // ✅ دالة للتنبيه عند الفشل النهائي بعد استنفاد جميع المحاولات
    public function failed(\Throwable $exception): void
    {
        Log::critical("Sync permanently failed for Product ID {$this->product->id}: " . $exception->getMessage());
        
        // يمكنك إضافة كود هنا لإرسال إشعار للأدمن (مثلاً عبر Telegram أو البريد)
        // أو تحديث حقل في قاعدة البيانات مثل: $this->product->update(['sync_failed' => true]);
    }
}