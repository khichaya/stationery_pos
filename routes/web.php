<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\dashboardController;
use App\Http\Controllers\ProfileController;

// 🛡️ دالة مساعدة مركزية لفحص الصلاحيات بأمان دون تدمير الـ Resource
if (!function_exists('checkUserPermission')) {
    function checkUserPermission($permissionName) {
        $user = auth()->user();
        if (!$user) abort(403);
        if ($user->role === 'manager') return true;
        
        $perms = is_array($user->permissions) ? $user->permissions : (json_decode($user->permissions, true) ?? []);
        if (!in_array($permissionName, $perms)) {
            abort(403, 'عذراً، لا تملك الصلاحية الأمنية الكافية لدخول هذه الشاشة.');
        }
    }
}

// ---------------------------------------------------------
// مسارات الزوار (غير المسجلين) - التوجيه لشاشة تسجيل الدخول
// ---------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm']);
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});
// routes/web.php
Route::get('/print-invoice/{sale}', function ($saleId) {
    $sale = Sale::with('items')->findOrFail($saleId);
    $setting = InstitutionSetting::first();
    return view('print.invoice', compact('sale', 'setting'));
})->name('print.invoice')->middleware('auth');
// ---------------------------------------------------------
// جميع المسارات التالية تتطلب أن يكون المستخدم مسجلاً للدخول
// ---------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    
    // 🟢 الرابط الرئيسي للوحة التحكم
    // 🟢 تأمين الشاشة الرئيسية بالكامل بناءً على الصلاحيات
// 🟢 تأمين الشاشة الرئيسية بناءً على الصلاحية الجديدة المخصصة
Route::get('/dashboard', function() {
    $user = auth()->user();
    $perms = is_array($user->permissions) ? $user->permissions : (json_decode($user->permissions, true) ?? []);
    
    // الفحص يعتمد الآن على الصلاحية المخصصة 'access_dashboard'
    if ($user->role !== 'manager' && !in_array('access_dashboard', $perms)) {
        
        // التوجيه التلقائي المباشر للـ POS إذا كان يملك صلاحيتها
        if ($user->role === 'cashier' || in_array('access_pos', $perms)) {
            return redirect()->route('pos.index');
        }
        
        abort(403, 'عذراً، لا تملك صلاحية الوصول للوحة التحكم الرئيسية لنظام بيان.');
    }
    
    return app(dashboardController::class)->index();
})->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // 1️⃣ واجهة نقطة البيع (POS) والمبيعات
    Route::get('/pos', function() {
        checkUserPermission('access_pos');
        return app(PosController::class)->index();
    })->name('pos.index');

    Route::post('/pos/store', [PosController::class, 'store'])->name('pos.store');

    // 2️⃣ شاشة النسخ الاحتياطي والاسترجاع
    Route::get('/database-backups', function () {
        checkUserPermission('database_backup');
        return view('settings.backups');
    })->name('backups.index');

    // 3️⃣ شاشة إدارة المستخدمين والصلاحيات (للمدير فقط)
    Route::get('/users-management', function () {
        if (auth()->user()->role !== 'manager') {
            abort(403, 'عذراً، شاشة تسيير العمال مخصصة حصرياً للمدير العام!');
        }
        return view('settings.users');
    })->name('users.index');

    // 4️⃣ أرشيف المبيعات
    Route::get('/sales-archive', function () {
        checkUserPermission('view_dashboard_stats');
        return view('sales.index');
    })->name('sales.archive');

    // 5️⃣ شاشة لوحة التقارير الشاملة
    Route::get('/business-reports', function () {
        checkUserPermission('view_dashboard_stats');
        return view('reports.index');
    })->name('reports.index');

    // 6️⃣ شاشة الخدمات والبحوث الإلكترونية
    Route::get('/services', function () {
        checkUserPermission('manage_services');
        return view('services.index');
    })->name('services.index');

    // 7️⃣ الزبائن وإدارة الديون والكريدي
    Route::get('/customers', function () {
        checkUserPermission('manage_debts');
        return view('customers.index');
    })->name('customers.index');
    
    Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');

// 8️⃣ إدارة المنتجات المتطورة بالمخزن (تفعيل محرك تشغيل السلع الفردية والصناديق لـ بيان)
   Route::livewire('/products', 'products.index')->name('products.index');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('units', UnitController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchases', PurchaseController::class);
    // 🟢 صفحة إعدادات الهيدر والفوتر وهوية المؤسسة المباشرة
    // 🟢 استدعاء صفحة الإعدادات المدمجة بأمان بدون كلاس مستقل
       
    // 9️⃣ المصاريف اليومية وصندوق المال
    Route::get('/expenses', function() {
        checkUserPermission('manage_expenses');
        return app(ExpenseController::class)->index();
    })->name('expenses.index');
    Route::resource('expenses', ExpenseController::class)->except(['index', 'show', 'edit', 'update']);
    // routes/web.php
// routes/web.php
// routes/web.php
Route::get('/settings/invoice-identity', function () {
    return view('settings.invoice-identity-page'); // ← غيّرنا الاسم
})->name('settings.identity')->middleware('auth');
    // 🔟 التقارير الفرعية وحركة المخزون للطباعة
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', function() { checkUserPermission('view_dashboard_stats'); return app(ReportController::class)->sales(); })->name('sales');
        Route::get('/stock', function() { checkUserPermission('view_dashboard_stats'); return app(ReportController::class)->stockAlerts(); })->name('stock.alerts');
        Route::get('/profits', function() { checkUserPermission('view_dashboard_stats'); return app(ReportController::class)->profits(); })->name('profits');
        Route::get('/movements', function() { checkUserPermission('view_dashboard_stats'); return app(ReportController::class)->movements(); })->name('movements');
    });

    // 1️⃣1️⃣ إعدادات النظام العامة
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', function() {
            if (auth()->user()->role !== 'manager') abort(403);
            return app(SettingController::class)->index();
        })->name('index');
        
        Route::post('/update', [SettingController::class, 'update'])->name('update'); 
        Route::post('/backup', [SettingController::class, 'createBackup'])->name('backup'); 
    });

    // 1️⃣2️⃣ إعدادات الملف الشخصي
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});