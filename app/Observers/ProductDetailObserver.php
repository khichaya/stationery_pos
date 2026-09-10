<?php

namespace App\Observers;

use App\Models\ProductDetail;
use App\Jobs\SyncProductToShopJob;

class ProductDetailObserver
{
    public function saved(ProductDetail $detail): void
    {
        SyncProductToShopJob::dispatch($detail->product);
    }

    public function deleted(ProductDetail $detail): void
    {
        SyncProductToShopJob::dispatch($detail->product);
    }
}