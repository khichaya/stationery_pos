<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Jobs\SyncProductToShopJob;

class SyncAllProductsToShop extends Command
{
    /**
     * اسم الأمر الذي ستكتبه في التيرمينال
     */
    protected $signature = 'shop:sync-all';

    /**
     * وصف الأمر
     */
    protected $description = 'مزامنة جميع المنتجات الموجودة في الـ POS إلى المتجر السحابي';

    /**
     * تنفيذ الأمر
     */
    public function handle()
    {
        // جلب جميع المنتجات من قاعدة البيانات المحلية
        $products = Product::all();
        
        if ($products->isEmpty()) {
            $this->info('لا توجد منتجات لمزامنتها.');
            return;
        }

        $this->info("جاري إعداد مزامنة {$products->count()} منتج إلى المتجر السحابي...");

        // إنشاء شريط تقدم في التيرمينال لكي ترى العملية
        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $product) {
            // دفع كل منتج ك مهمة (Job) إلى قائمة الانتظار
            SyncProductToShopJob::dispatch($product);
            $bar->advance();
        }

        $bar->finish();
        
        $this->info("\nتم إرسال جميع المنتجات إلى قائمة الانتظار بنجاح!");
        $this->warn('تأكد من أن أمر [php artisan queue:work] يعمل في خلفية النظام لمعالجة المهام.');
    }
}