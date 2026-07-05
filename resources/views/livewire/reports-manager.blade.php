 <?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    // دالة لتحديث البيانات فوراً عند فتح الصفحة
    public function render()
    {
        // 1. حساب رأس المال العام المستثمر في المخزن (بناءً على سعر الشراء × الكمية الحالية)
        $total_capital = Product::sum(DB::raw('purchase_price * current_stock'));

        // 2. الديون الإيجابية والسلبية (حسب إشارة ومفهوم جدول الزبائن الفعلي لديك)
        $total_positive_debt = Customer::where('total_debt', '>', 0)->sum('total_debt'); // كريدي (عليهم للمكتبة)
        $total_negative_debt = Customer::where('total_debt', '<', 0)->sum('total_debt') * -1; // فضلة (لهم عند المكتبة)

        // 3. السلع التي لحقت أو شارفت على الحد الأدنى (النواقص)
        $low_stock_products = Product::whereRaw('current_stock <= min_stock_alert')
                                     ->orderBy('current_stock', 'asc')
                                     ->take(5)->get();

        // 4. الخدمات الأكثر طلباً وإيراداً
        $top_services = Service::select('service_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as total_revenue'))
                               ->groupBy('service_type')
                               ->orderBy('count', 'desc')
                               ->take(5)->get();

        // 5. الزبون الذي يملك أكبر دين (صاحب أعلى كريدي معلق)
        $top_debtor_customer = Customer::where('total_debt', '>', 0)
                                       ->orderBy('total_debt', 'desc')
                                       ->first();

        // ✨ إضافات ذكية للمسير:
        // أ. إجمالي المبيعات المحققة في النظام كاش وآجل
        $total_sales_revenue = Sale::sum('total_amount');
        
        // ب. عدد الأصناف والسلع الإجمالية في الرفوف
        $total_products_count = Product::count();
        $total_pieces_count = Product::sum('current_stock');

        return view('livewire.reports-manager', [
            'total_capital' => $total_capital,
            'total_positive_debt' => $total_positive_debt,
            'total_negative_debt' => $total_negative_debt,
            'low_stock_products' => $low_stock_products,
            'top_services' => $top_services,
            'top_debtor_customer' => $top_debtor_customer,
            'total_sales_revenue' => $total_sales_revenue,
            'total_products_count' => $total_products_count,
            'total_pieces_count' => $total_pieces_count,
        ]);
    }
};
?>

<div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-white" style="background: linear-gradient(135deg, #2b2d42, #11131c);">
                <span class="text-xs opacity-75 d-block mb-1">📦 قيمة رأس المال بالمخزن (السلع):</span>
                <h3 class="fw-bold font-monospace text-warning mb-0">{{ number_format($total_capital, 2) }} <span class="fs-6">دج</span></h3>
                <small class="text-xs text-white-50 mt-1 d-block">مبني على (سعر الشراء × الكمية الحالية)</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-white" style="background: linear-gradient(135deg, #d90429, #ef233c);">
                <span class="text-xs opacity-75 d-block mb-1">📕 ديون إيجابية للمحل (الكريدي بالخارج):</span>
                <h3 class="fw-bold font-monospace mb-0">{{ number_format($total_positive_debt, 2) }} <span class="fs-6">دج</span></h3>
                <small class="text-xs text-white-50 mt-1 d-block">⚠️ أموال مطلوب تحصيلها من الزبائن</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-white" style="background: linear-gradient(135deg, #2ec4b6, #0f9f90);">
                <span class="text-xs opacity-75 d-block mb-1">🟢 ديون سلبية (فضلات الزبائن عندك):</span>
                <h3 class="fw-bold font-monospace mb-0">{{ number_format($total_negative_debt, 2) }} <span class="fs-6">دج</span></h3>
                <small class="text-xs text-white-50 mt-1 d-block">✨ مبالغ زائدة تركها الزبائن في حساباتهم</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-white" style="background: linear-gradient(135deg, #872061, #b13384);">
                <span class="text-xs opacity-75 d-block mb-1">📊 إجمالي إيرادات المبيعات (السينال):</span>
                <h3 class="fw-bold font-monospace text-warning mb-0">{{ number_format($total_sales_revenue, 2) }} <span class="fs-6">دج</span></h3>
                <small class="text-xs text-white-50 mt-1 d-block">📈 مجموع الفواتير الموثقة بالنظام</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">⚠️ السلع والمستلزمات الموشكة على النفاد</h6>
                    <span class="badge bg-danger-subtle text-danger px-2 py-1 text-xs">تحت حد الطلب</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">اسم السلعة / الكتاب</th>
                                <th>الرف / المكان</th>
                                <th>المخزون الحالي</th>
                                <th>حد التنبيه</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($low_stock_products as $prod)
                                <tr class="table-danger-subtle">
                                    <td class="text-start fw-bold text-dark">{{ $prod->name }}</td>
                                    <td><span class="badge bg-secondary">{{ $prod->location ?: 'غير محدد' }}</span></td>
                                    <td class="fw-bold text-danger font-monospace">{{ $prod->current_stock }} قطعة</td>
                                    <td class="text-muted font-monospace">{{ $prod->min_stock_alert }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted p-4">🟢 ممتاز! لا توجد نواقص في الرفوف حالياً.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

          <!-- 👤 كرت الزبون صاحب أكبر مديونية المطور بعد تصحيح الألوان -->
<div class="card border-0 shadow-sm rounded-4 mt-4 p-4 bg-gradient text-white" style="background: linear-gradient(135deg, #1e1e2f, #252538);">
    <h6 class="fw-bold text-warning mb-3">👤 الزبون صاحب أكبر مديونية (الكريدي الأكبر)</h6>
    @if($top_debtor_customer)
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm">
            <div>
                <!-- 🚀 تم تغيير الفئة هنا إلى text-dark ليكون الاسم بارع وواضح جداً باللون الأسود الداكن -->
                <h5 class="fw-bold mb-1 text-dark">{{ $top_debtor_customer->name }}</h5>
                <!-- 🚀 تم تغيير الفئة هنا إلى text-muted لتكون واضحة ومقروءة بالرمادي المحاسبي المحترف -->
                <span class="text-xs font-monospace text-muted">📱 هاتف: {{ $top_debtor_customer->phone ?: 'غير مسجل' }}</span>
            </div>
            <div class="text-end">
                <span class="text-xs d-block text-secondary fw-bold">قيمة الدين المستحق:</span>
                <span class="fs-4 fw-bold font-monospace text-danger">{{ number_format($top_debtor_customer->total_debt, 2) }} دج</span>
            </div>
        </div>
    @else
        <div class="text-muted text-xs">🟢 لا يوجد زبائن مديونين حالياً في النظام.</div>
    @endif

    <div class="row g-2 mt-3 text-center small border-top border-secondary pt-3">
        <div class="col-6 border-end border-secondary">
            <span class="text-xs opacity-50 d-block">تنوع العناوين والسلع:</span>
            <span class="fw-bold font-monospace text-info fs-5">{{ $total_products_count }} صنف</span>
        </div>
        <div class="col-6">
            <span class="text-xs opacity-50 d-block">إجمالي عدد القطع الكلي:</span>
            <span class="fw-bold font-monospace text-success fs-5">{{ number_format($total_pieces_count) }} وحدة</span>
        </div>
    </div>
</div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-dark mb-0">🛠️ الخدمات الإلكترونية الأكثر طلباً وإيراداً</h6>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">نوع الخدمة / العمل</th>
                                <th>عدد مرات التقديم</th>
                                <th>إجمالي الإيرادات المحققة</th>
                                <th>الترتيب الاستراتيجي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($top_services as $index => $service)
                                <tr>
                                    <td class="text-start fw-bold text-dark">{{ $service->service_type }}</td>
                                    <td class="font-monospace fw-bold">{{ $service->count }} حركة تقديم</td>
                                    <td class="font-monospace fw-bold text-success">{{ number_format($service->total_revenue, 2) }} دج</td>
                                    <td>
                                        @if($index == 0)
                                            <span class="badge bg-warning text-dark fw-bold">🥇 الأعلى طلباً</span>
                                        @elseif($index == 1)
                                            <span class="badge bg-light text-dark font-monospace">🥈 الثاني</span>
                                        @else
                                            <span class="badge bg-light text-muted font-monospace">⭐</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted p-4">📥 لا توجد حركات خدمات مسجلة حتى الآن.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-xs { font-size: 0.75rem; }
    .table-danger-subtle { background-color: #fff2f2 !important; }
</style>