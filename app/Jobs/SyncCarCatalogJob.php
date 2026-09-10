<?php

namespace App\Jobs;

use App\Models\CarCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SyncCarCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $carId;

    public function __construct($carId)
    {
        $this->carId = $carId;
    }

    public function handle(): void
    {
        $car = CarCatalog::find($this->carId);
        if (!$car) return;

        // ✅ استخدام config بدلاً من env
        $url = rtrim(config('services.web_shop.url'), '/') . '/api/sync-car-catalog';
        $token = config('services.web_shop.token');

        if (blank($url) || blank($token)) {
            Log::error("Sync Job: WEB_SHOP_URL or WEB_SHOP_TOKEN is not set in config.");
            return;
        }

        $request = Http::timeout(30)->withToken($token)->withoutVerifying();

        $data = [
            'brand' => $car->brand,
            'model' => $car->model,
            'years' => $car->years,
        ];

        if ($car->brand_logo && Storage::disk('public')->exists($car->brand_logo)) {
            $request = $request->attach('brand_logo', Storage::disk('public')->get($car->brand_logo), basename($car->brand_logo));
        }
        if ($car->model_image && Storage::disk('public')->exists($car->model_image)) {
            $request = $request->attach('model_image', Storage::disk('public')->get($car->model_image), basename($car->model_image));
        }

        $response = $request->post($url, $data);

        if (!$response->successful()) {
            Log::error("Car Catalog Sync Failed: " . $response->body());
        }
    }
}