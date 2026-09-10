<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncProductDeleteToShopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $backoff = 60;

    protected $productCode;

    public function __construct(string $productCode)
    {
        $this->productCode = $productCode;
    }

    public function handle(): void
    {
        // ✅ استخدام config بدلاً من env
        $url = rtrim(config('services.web_shop.url'), '/') . '/api/delete-product';
        $token = config('services.web_shop.token');

        if (blank($url) || blank($token)) {
            Log::error("Sync Job: WEB_SHOP_URL or WEB_SHOP_TOKEN is not set in config.");
            return;
        }

        try {
            // ✅ استخدام POST بدلاً من DELETE وتجاوز شهادة SSL
            $response = Http::timeout(30)->withToken($token)->withoutVerifying()->post($url, [
                'code' => $this->productCode,
            ]);

            // ✅ إذا كانت الاستجابة 404 (غير موجود)، نعتبر المزامنة ناجحة
            if ($response->status() === 404) {
                Log::info("Product with code {$this->productCode} was not found on the shop. Considered deleted successfully.");
                return;
            }

            if (!$response->successful()) {
                Log::error("Delete sync failed for code {$this->productCode}: " . $response->status() . " - " . $response->body());
                throw new \Exception("Delete sync failed: " . $response->status() . " - " . $response->body());
            }

            Log::info("Product with code {$this->productCode} deleted successfully from shop.");

        } catch (\Exception $e) {
            Log::error("Delete sync exception for code {$this->productCode}: " . $e->getMessage());
            throw $e;
        }
    }
}