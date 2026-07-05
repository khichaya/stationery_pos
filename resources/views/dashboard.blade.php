@extends('layouts.app')

@section('content')

<style>
    .stat-card {
        border-radius: 14px;
        padding: 1.4rem 1.5rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        min-height: 120px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .stat-card .icon-circle {
        position: absolute;
        left: -10px;
        bottom: -10px;
        font-size: 4.5rem;
        opacity: .18;
    }
    .stat-card .label {
        font-size: .85rem;
        font-weight: 600;
        opacity: .9;
    }
    .stat-card .value {
        font-size: 1.7rem;
        font-weight: 800;
        margin-top: .25rem;
    }
    .stat-card.navy   { background: linear-gradient(135deg, #0e3854, #145a82); }
    .stat-card.cyan   { background: linear-gradient(135deg, #0d7cb5, #14a3e8); }
    .stat-card.magenta{ background: linear-gradient(135deg, #872061, #b32d82); }
    .stat-card.dark   { background: linear-gradient(135deg, #1f2937, #374151); }

    .section-title {
        font-weight: 800;
        color: var(--primary-navy, #0e3854);
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }

    .quick-btn {
        border: none;
        border-radius: 12px;
        padding: 1.1rem .75rem;
        text-align: center;
        font-weight: 700;
        color: #fff;
        text-decoration: none;
        display: block;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .quick-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,.12);
        color: #fff;
    }
    .quick-btn i { font-size: 1.6rem; display: block; margin-bottom: .4rem; }

    .table-card thead th {
        background: #f4f7f9;
        color: #0e3854;
        font-weight: 700;
        border: none;
        font-size: .85rem;
    }
    .table-card td {
        vertical-align: middle;
        font-size: .9rem;
    }

    .badge-status {
        border-radius: 20px;
        padding: .35rem .75rem;
        font-weight: 600;
        font-size: .75rem;
    }
    
    .chart-container {
        position: relative;
        height: 260px;
        width: 100%;
    }
</style>

{{-- Page header --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color: var(--primary-navy, #0e3854);">
            لوحة التحكم الماليّة الذكية
        </h4>
        <span class="text-muted small">نظرة عامة على نشاط اليوم — {{ now()->translatedFormat('l، d F Y') }}</span>
    </div>
    <a href="/pos" class="btn text-white fw-bold px-4 shadow-sm" style="background: var(--magenta-purple, #872061); border-radius: 10px;">
        <i class="bi bi-cart3 me-1"></i> فتح شاشة البيع السريع
    </a>
</div>

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card cyan">
            <i class="bi bi-cash-coin icon-circle"></i>
            <div class="label">مبيعات اليوم</div>
            <div class="value">{{ number_format($todaySales ?? 0, 2) }} دج</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card navy">
            <i class="bi bi-box-seam icon-circle"></i>
            <div class="label">عدد المنتجات بالمخزن</div>
            <div class="value">{{ $productsCount ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card magenta">
            <i class="bi bi-exclamation-triangle icon-circle"></i>
            <div class="label">منتجات ناقصة المخزون</div>
            <div class="value">{{ $lowStockCount ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card dark">
            <i class="bi bi-people icon-circle"></i>
            <div class="label">إجمالي ديون الزبائن بالخارج</div>
            <div class="value">{{ number_format($totalDebts ?? 0, 2) }} دج</div>
        </div>
    </div>
</div>

{{-- 📊 قسم الرسوم البيانية المتطورة (منحنيات، أعمدة ودوائر نسبية) --}}
<div class="row g-3 mb-4">
    <!-- 1. منحنى مبيعات آخر 7 أيام -->
    <div class="col-lg-6">
        <div class="card p-3 border-0 shadow-sm rounded-4">
            <span class="section-title mb-3">📈 منحنى أداء المبيعات (آخر 7 أيام)</span>
            <div class="chart-container">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 2. أعمدة بيانية للخدمات الأكثر طلباً -->
    <div class="col-lg-3">
        <div class="card p-3 border-0 shadow-sm rounded-4">
            <span class="section-title mb-3">📊 الخدمات الأكثر طلباً</span>
            <div class="chart-container">
                <canvas id="servicesBarChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 3. دائرة نسبية لتوزيع طرق الدفع -->
    <div class="col-lg-3">
        <div class="card p-3 border-0 shadow-sm rounded-4">
            <span class="section-title mb-3">🍕 طرق السداد بالفواتير</span>
            <div class="chart-container">
                <canvas id="paymentMethodsPieChart"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Quick actions --}}
<div class="section-title">إجراءات سريعة</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="/pos" class="quick-btn" style="background: linear-gradient(135deg, #0d7cb5, #14a3e8);">
            <i class="bi bi-cart3"></i> نقطة البيع
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('products.index') }}" class="quick-btn" style="background: linear-gradient(135deg, #0e3854, #145a82);">
            <i class="bi bi-box-seam"></i> إدارة المنتجات
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/services" class="quick-btn" style="background: linear-gradient(135deg, #872061, #b32d82);">
            <i class="bi bi-bag-plus"></i> شاشة الخدمات
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="/business-reports" class="quick-btn" style="background: linear-gradient(135deg, #374151, #1f2937);">
            <i class="bi bi-bar-chart-line"></i> عرض التقارير المتقدمة
        </a>
    </div>
</div>

{{-- Recent activity + low stock --}}
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card p-3 table-card border-0 shadow-sm rounded-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="section-title mb-0">آخر عمليات البيع الحالية</span>
                <a href="/sales-archive" class="small text-decoration-none fw-bold">أرشيف الفواتير ←</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-center">
                    <thead>
                        <tr>
                            <th>الفاتورة</th>
                            <th class="text-start">الزبون</th>
                            <th>المبلغ الصافي</th>
                            <th>الحالة</th>
                            <th>الوقت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($recentSales ?? []) as $sale)
                        <tr>
                            <td class="fw-bold">#{{ $sale->id }}</td>
                            <td class="text-start fw-bold text-dark">{{ optional($sale->customer)->name ?? 'زبون عابر' }}</td>
                            <td class="fw-bold text-success">{{ number_format($sale->total_amount, 2) }} دج</td>
                            <td>
                                @if($sale->payment_method === 'full')
                                    <span class="badge-status bg-success-subtle text-success">كامل</span>
                                @elseif($sale->payment_method === 'partial')
                                    <span class="badge-status bg-warning-subtle text-warning">جزئي</span>
                                @else
                                    <span class="badge-status bg-danger-subtle text-danger">دين</span>
                                @endif
                            </td>
                            <td class="text-muted small font-monospace">{{ $sale->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">لا توجد عمليات بيع مسجلة اليوم</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3 table-card border-0 shadow-sm rounded-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="section-title mb-0">تنبيهات رفوف المخزون</span>
                <span class="badge bg-danger text-white fw-bold">{{ $lowStockCount ?? 0 }} منتج مقترب</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-center">
                    <thead>
                        <tr>
                            <th class="text-start">اسم السلعة / المنتج</th>
                            <th>الكمية الحالية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($lowStockProducts ?? []) as $product)
                        <tr>
                            <td class="text-start fw-bold text-dark">{{ $product->name }}</td>
                            <td>
                                <span class="badge-status bg-danger-subtle text-danger fw-bold font-monospace">
                                    {{ $product->current_stock }} قطعة متبقية
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">كافة سلع ومستلزمات الرفوف في وضع ممتاز وعالي ✅</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 🚀 استدعاء سكريبت ومحرك الرسوم البيانية الذكي --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. معالجة وحقن منحنى المبيعات (خطي)
    // 🚀 إضافة شرط أمان: إذا لم يجد المتغير يضع مصفوفة فارغة لكي لا تنهار الصفحة
const salesdata = {!! json_encode($salesLast7days ?? []) !!};
    new Chart(document.getElementById('salesTrendChart'), {
        type: 'line',
        data: {
            labels: salesdata.map(item => item.date),
            datasets: [{
                label: 'حجم مبيعات المحل (دج)',
                data: salesdata.map(item => item.total),
                borderColor: '#0d7cb5',
                backgroundColor: 'rgba(13, 124, 181, 0.08)',
                fill: true,
                tension: 0.3,
                borderWidth: 3
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // 2. معالجة وحقن الخدمات الأكثر طلباً (أعمدة بيانية)
    const servicesdata = {!! json_encode($topServicesChart ?? []) !!};

    new Chart(document.getElementById('servicesBarChart'), {
        type: 'bar',
        data: {
            labels: servicesdata.map(item => item.service_type),
            datasets: [{
                label: 'عدد المرات',
                data: servicesdata.map(item => item.count),
                backgroundColor: '#872061',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // 3. معالجة وحقن طرق الدفع (دوائر نسبية)
    const payDist = {!! json_encode($paymentMethodsDist ?? []) !!};
    new Chart(document.getElementById('paymentMethodsPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['دفع كامل', 'دفع جزئي', 'دين كامل'],
            datasets: [{
                data: [payDist['full'] || 0, payDist['partial'] || 0, payDist['debt'] || 0],
                backgroundColor: ['#2ec4b6', '#ff9f1c', '#e71d36']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>

@endsection