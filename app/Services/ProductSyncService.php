<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductSyncService
{
    public function syncProduct(Product $product)
    {
        $url = env('WEB_SHOP_URL') . '/api/sync-product';
        $token = env('WEB_SHOP_TOKEN');

        // ✅ تحميل علاقة التفاصيل لتجنب N+1
        $product->loadMissing('details');

        // تحديد مسار الصورة
        $imgPath = $product->image ?? ($product->images->first()->image_path ?? null);
        $fullPath = $imgPath ? storage_path('app/public/' . $imgPath) : null;

        // ✅ إعداد بيانات المنتج الأساسية
        $data = [
            'code'        => $product->code ?? ('PROD-' . $product->id),
            'name'        => $product->name,
            'price_1'     => $product->price_1 ?? $product->price ?? 0,
            'quantity'    => $product->quantity ?? 0,
            'description' => $product->description ?? null,
            'is_active'   => $product->is_active ?? true,
        ];

        // ✅ إضافة تفاصيل المنتج إن وُجدت
        if ($product->details) {
            $data['details_title']     = $product->details->title;
            $data['details_content']   = $product->details->content;
            $data['details_published'] = $product->details->is_published ? 1 : 0;
            
            Log::info("Product details included in sync", [
                'product_id' => $product->id,
                'title' => $product->details->title
            ]);
        }

        Log::info("Attempting sync to shop", [
            'product_id' => $product->id,
            'code' => $data['code'],
            'image_path' => $fullPath,
            'has_details' => $product->details ? true : false
        ]);

        // إعداد الـ HTTP Request
        $request = Http::withToken($token)->timeout(30);

        if ($fullPath && file_exists($fullPath)) {
            $response = $request
                ->attach('image_file', file_get_contents($fullPath), basename($fullPath))
                ->post($url, $data);
        } else {
            Log::warning("Image file not found on disk: " . $fullPath);
            $response = $request->post($url, $data);
        }

        Log::info("Sync Response", [
            'status' => $response->status(),
            'body' => $response->body(),
            'product_id' => $product->id
        ]);

        // ✅ إرجاع الـ response للتعامل معه في الـ Job
        return $response;
    }

    /**
     * مزامنة حذف المنتج
     */
    
    public function deleteProduct(string $code)
    {
        $url = env('WEB_SHOP_URL') . '/api/delete-product';
        $token = env('WEB_SHOP_TOKEN');

        $response = Http::withToken($token)->timeout(15)->post($url, [
            'code' => $code,
        ]);

        Log::info("Delete sync response", [
            'code' => $code,
            'status' => $response->status()
        ]);

        return $response;
    }
}