<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Favorite;

class FavoritesSeeder extends Seeder
{
    public function run(): void
    {
        $favorites = [
            ['name' => 'فوتوكوبي A4', 'price' => 10.00, 'icon' => '📄', 'color' => '#4a90d9', 'sort_order' => 1],
            ['name' => 'فوتوكوبي A3', 'price' => 15.00, 'icon' => '📃', 'color' => '#4a90d9', 'sort_order' => 2],
            ['name' => 'طباعة ملونة', 'price' => 25.00, 'icon' => '🌈', 'color' => '#e74c3c', 'sort_order' => 3],
            ['name' => 'تصوير هوية', 'price' => 50.00, 'icon' => '📷', 'color' => '#27ae60', 'sort_order' => 4],
            ['name' => 'تصوير جواز سفر', 'price' => 100.00, 'icon' => '🛂', 'color' => '#27ae60', 'sort_order' => 5],
            ['name' => 'تصحيح امتحان', 'price' => 20.00, 'icon' => '✅', 'color' => '#f39c12', 'sort_order' => 6],
            ['name' => 'تجليد حراري', 'price' => 150.00, 'icon' => '📕', 'color' => '#8e44ad', 'sort_order' => 7],
            ['name' => 'تجليد سلكي', 'price' => 80.00, 'icon' => '📎', 'color' => '#8e44ad', 'sort_order' => 8],
            ['name' => 'تغليف كتاب', 'price' => 30.00, 'icon' => '📦', 'color' => '#16a085', 'sort_order' => 9],
            ['name' => 'شرائح عرض', 'price' => 5.00, 'icon' => '🎞️', 'color' => '#d35400', 'sort_order' => 10],
            ['name' => 'ارسال فاكس', 'price' => 40.00, 'icon' => '📠', 'color' => '#7f8c8d', 'sort_order' => 11],
            ['name' => 'شحن رصيد', 'price' => 0.00, 'icon' => '📱', 'color' => '#2ecc71', 'sort_order' => 12],
        ];

        foreach ($favorites as $fav) {
            Favorite::create($fav);
        }
    }
}
