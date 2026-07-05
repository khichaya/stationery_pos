<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // 👈 مهم جداً لتشغيل عمليات حساب قاعدة البيانات

class dashboardController extends Controller
{
    public function index()
    {
        // 1. مبيعات اليوم
        $todaySales = \App\Models\Sale::wheredate('created_at', today())->sum('total_amount');

        // 2. عدد المنتجات الكلي
        $productsCount = \App\Models\Product::count();

        // 3. عدد المنتجات ناقصة المخزون بناءً على حقل الـ min_stock_alert الخاص بك
        $lowStockCount = \App\Models\Product::whereRaw('current_stock <= min_stock_alert')->count();

        // 4. ديون الزبائن الكلية (مجموع الحقول الموجبة)
        $totalDebts = \App\Models\Customer::where('total_debt', '>', 0)->sum('total_debt');

        // 5. آخر 5 عمليات بيع
        $recentSales = \App\Models\Sale::with('customer')->orderBy('created_at', 'desc')->take(5)->get();

        // 6. قائمة المنتجات منخفضة المخزون
        $lowStockProducts = \App\Models\Product::whereRaw('current_stock <= min_stock_alert')
                                               ->orderBy('current_stock', 'asc')
                                               ->take(5)->get();

        // 📊 إحصائيات المنحنى البياني (مبيعات آخر 7 أيام)
        $salesLast7days = \App\Models\Sale::select(
            DB::raw("daTE_FORMAT(created_at, '%Y-%m-%d') as date"),
            DB::raw("SUM(total_amount) as total")
        )
        ->where('created_at', '>=', now()->subdays(6))
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

        // 📊 إحصائيات الدوائر النسبية (طرق الدفع: كامل، جزئي، دين)
        $paymentMethodsDist = \App\Models\Sale::select('payment_method', DB::raw('count(*) as count'))
                                              ->groupBy('payment_method')
                                              ->get()
                                              ->pluck('count', 'payment_method')
                                              ->toArray();

        // 📊 إحصائيات الأعمدة البيانية (الخدمات الـ 5 الأكثر طلباً)
        $topServicesChart = \App\Models\Service::select('service_type', DB::raw('COUNT(*) as count'))
                                               ->groupBy('service_type')
                                               ->orderBy('count', 'desc')
                                               ->take(5)->get();

       return view('dashboard', [
    'todaySales' => $todaySales,
    'productsCount' => $productsCount,
    'lowStockCount' => $lowStockCount,
    'totalDebts' => $totalDebts,
    'recentSales' => $recentSales,
    'lowStockProducts' => $lowStockProducts,
    'salesLast7days' => $salesLast7days, // 👈 تأكد من وجود هذا السطر
    'paymentMethodsDist' => $paymentMethodsDist, // 👈 وتأكد من وجود هذا
    'topServicesChart' => $topServicesChart // 👈 وهذا أيضاً
]);
    }
}