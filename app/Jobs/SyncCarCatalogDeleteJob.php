<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncCarCatalogDeleteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $brand;
    protected $model;

    public function __construct($brand, $model)
    {
        $this->brand = $brand;
        $this->model = $model;
    }

    public function handle(): void
    {
        $url = rtrim(config('services.web_shop.url'), '/') . '/api/delete-car-catalog';
        $token = config('services.web_shop.token');

        if (blank($url) || blank($token)) return;

        $response = Http::withToken($token)->withoutVerifying()->post($url, [
            'brand' => $this->brand,
            'model' => $this->model,
        ]);

        if (!$response->successful()) {
            Log::error("Car Catalog Delete Sync Failed: " . $response->body());
        }
    }
}