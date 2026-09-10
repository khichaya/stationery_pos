<?php

namespace App\Observers;

use App\Models\Product;
use App\Jobs\SyncProductToShopJob;
use App\Jobs\SyncProductDeleteToShopJob;
class ProductObserver
{
    public function created(Product $product): void
    {
        SyncProductToShopJob::dispatch($product);
    }

    public function updated(Product $product): void
    {
        SyncProductToShopJob::dispatch($product);
    }
    public function deleted(Product $product): void
{
    // نمرر كود المنتج للـ Job قبل إتمام حذف السجل محلياً
    $code = $product->code ?? ('PROD-' . $product->id);
    
    SyncProductDeleteToShopJob::dispatch($code);
}
}