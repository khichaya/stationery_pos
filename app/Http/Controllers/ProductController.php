<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * عرض الصفحة الرئيسية لإدارة المخزن والسلع
     */
    public function index()
    {
        return view('products.index');
    }

    // يمكنك ترك بقية الدوال (create, store, edit, update, destroy) فارغة 
    // لأن مكون Livewire (ProductManager) هو من يتولى هذه العمليات لحظياً.
}