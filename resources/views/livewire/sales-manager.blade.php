<?php

use Livewire\Component;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // متغيرات البحث والفلترة
    public $search = '';
    public $from_date = null;
    public $to_date = null;
    public $selected_sale = null; 
    public $selected_sale_profit = 0; 
    public $selected_sale_capital = 0; 

    // ✅ تعيين تاريخ اليوم تلقائياً عند فتح الصفحة
    public function mount()
    {
        $this->from_date = now()->format('Y-m-d');
        $this->to_date = now()->format('Y-m-d');
    }

    // دوال إعادة الترقيم عند تغيير الفلاتر
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFromDate() { $this->resetPage(); }
    public function updatingToDate() { $this->resetPage(); }

    // دالة فتح تفاصيل الفاتورة لرؤية السلع السابقة وحساب أرباحها
    public function showSaleDetails($id)
    {
        $this->selected_sale = Sale::with('items')->findOrFail($id);

        $this->selected_sale_profit = 0;
        $this->selected_sale_capital = 0;
        
        $productIds = $this->selected_sale->items->whereNotNull('product_id')->pluck('product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($this->selected_sale->items as $item) {
            $cost = 0;
            // ✅ إذا كانت سلعة مسجلة في المخزن: نحسب التكلفة من عمود purchase_price
            if ($item->product_id && isset($products[$item->product_id])) {
                $product = $products[$item->product_id];
                $cost = $product->purchase_price ?? 0; 
            } 
            // ✅ إذا كانت خدمة / مبيعات سريعة (بدون product_id): نطبق قاعدة 70% رأس مال
            elseif (empty($item->product_id)) {
                $cost = $item->unit_price * 0.70;
            }

            $this->selected_sale_capital += $cost * $item->quantity;
            $this->selected_sale_profit += ($item->unit_price - $cost) * $item->quantity;
        }
    }

    // 🗑️ دالة إلغاء الفاتورة بالكامل
    public function voidSale($id)
    {
        DB::beginTransaction();
        try {
            $sale = Sale::with('items')->findOrFail($id);

            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)->increment('current_stock', $item->quantity);
                }
            }

            if (in_array($sale->payment_method, ['partial', 'debt']) && $sale->customer_id) {
                Customer::where('name', optional($sale->customer)->name)
                        ->where('observation', 'like', '%فاتورة رقم #' . $sale->id . '%')
                        ->delete();
            }

            $sale->items()->delete();
            $sale->delete();

            DB::commit();
            
            $this->selected_sale = null;
            session()->flash('success', 'تم إلغاء الفاتورة بنجاح، وإعادة كافة السلع المبيوعة للمخزن وتصفير الدين المترتب عليها!');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'حدث خطأ أثناء إلغاء الفاتورة: ' . $e->getMessage());
        }
    }

    // دالة إرسال الفاتورة لإعادة التعديل والتبديل في شاشة الـ POS
    public function editAndLoadToCart($id)
    {
        DB::beginTransaction();
        try {
            $sale = Sale::with('items')->findOrFail($id);

            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)->increment('current_stock', $item->quantity);
                }
            }

            if (in_array($sale->payment_method, ['partial', 'debt']) && $sale->customer_id) {
                Customer::where('name', optional($sale->customer)->name)
                        ->where('observation', 'like', '%فاتورة رقم #' . $sale->id . '%')
                        ->delete();
            }

            $cartdata = [];
            foreach ($sale->items as $item) {
                $key = $item->product_id ? 'p_' . $item->product_id : 'c_' . uniqid();
                $cartdata[$key] = [
                    'key' => $key,
                    'product_id' => $item->product_id,
                    'is_custom' => $item->product_id ? false : true,
                    'name' => $item->product_name,
                    'price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ];
            }

            session()->put('edit_sale_id', $sale->id);
            session()->put('edit_cart', $cartdata);
            session()->put('edit_customer_id', $sale->customer_id);
            session()->put('edit_discount', $sale->discount_amount);
            session()->put('edit_payment_method', $sale->payment_method);
            session()->put('edit_paid_amount', $sale->paid_amount);

            $sale->items()->delete();
            $sale->delete();

            DB::commit();

            return redirect()->route('pos.index');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'حدث خطأ أثناء تحميل الفاتورة للتعديل: ' . $e->getMessage());
        }
    }
    
    public function clearDates()
    {
        $this->from_date = null;
        $this->to_date = null;
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success p-2 small fw-bold">✨ {{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger p-2 small fw-bold">⚠️ {{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <!-- الجدول الأيمن: أرشيف الفواتير -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-3">🔍 أرشيف مبيعات مكتبة السلام والبحث عن السلع السابقة</h6>
                    
                    <div class="mb-2">
                        <input type="text" wire:model.live="search" class="form-control form-control-sm" placeholder="ابحث باسم الزبون، رقم الفاتورة، أو طريقة الدفع...">
                    </div>
                    
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="d-flex align-items-center gap-1">
                            <label class="small text-muted mb-0">من:</label>
                            <input type="date" wire:model.live="from_date" class="form-control form-control-sm">
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <label class="small text-muted mb-0">إلى:</label>
                            <input type="date" wire:model.live="to_date" class="form-control form-control-sm">
                        </div>
                        @if($from_date || $to_date)
                            <button wire:click="clearDates" class="btn btn-outline-secondary btn-sm">مسح التاريخ</button>
                        @endif
                    </div>
                </div>
                
                @php
                    // بناء الاستعلام الأساسي
                    $baseQuery = Sale::with('customer')
                        ->where(function($query) {
                            $query->where('id', 'like', '%'.$this->search.'%')
                                  ->orWhere('payment_method', 'like', '%'.$this->search.'%')
                                  ->orWhereHas('customer', function($q) {
                                      $q->where('name', 'like', '%'.$this->search.'%');
                                  });
                        });

                    if ($this->from_date) {
                        $baseQuery->whereDate('created_at', '>=', $this->from_date);
                    }
                    if ($this->to_date) {
                        $baseQuery->whereDate('created_at', '<=', $this->to_date);
                    }

                    // 1. حساب المبالغ المالية من جدول الفواتير
                    $sumQuery = clone $baseQuery;
                    $total_sum = $sumQuery->sum('total_amount');
                    $total_paid = $sumQuery->sum('paid_amount');
                    $total_debt = $total_sum - $total_paid;

                    // 2. ✅ حساب الأرباح ورأس المال لكل الفواتير المفلترة (يعتمد على purchase_price)
                    $saleIds = (clone $baseQuery)->pluck('id');
                    
                    $totalsCalc = DB::table('sale_items')
                        ->whereIn('sale_id', $saleIds)
                        ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
                        ->selectRaw('
                            SUM(sale_items.unit_price * sale_items.quantity) as gross_total,
                            SUM(
                                CASE
                                    WHEN sale_items.product_id IS NULL THEN (sale_items.unit_price * 0.70) * sale_items.quantity
                                    ELSE COALESCE(products.purchase_price, 0) * sale_items.quantity
                                END
                            ) as total_capital
                        ')
                        ->first();

                    $total_capital = $totalsCalc->total_capital ?? 0;
                    $total_profit = ($totalsCalc->gross_total ?? 0) - $total_capital;

                    // جلب الفواتير مع الترقيم
                    $salesList = $baseQuery->orderBy('created_at', 'desc')->paginate(10);
                @endphp

                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th class="text-start">الزبون</th>
                                <th>إجمالي الفاتورة</th>
                                <th>طريقة الدفع</th>
                                <th>📅 التاريخ</th>
                                <th>الخيارات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesList as $sale)
                                <tr class="{{ $selected_sale && $selected_sale->id === $sale->id ? 'table-primary' : '' }}">
                                    <td class="fw-bold">#{{ $sale->id }}</td>
                                    <td class="text-start fw-bold">
                                        {{ $sale->customer_id ? optional($sale->customer)->name : 'زبون عابر' }}
                                    </td>
                                    <td class="font-monospace fw-bold text-success">{{ number_format($sale->total_amount, 2) }} دج</td>
                                    <td>
                                        @if($sale->payment_method === 'full')
                                            <span class="badge bg-success">💵 كامل</span>
                                        @elseif($sale->payment_method === 'partial')
                                            <span class="badge bg-warning text-dark">➗ جزئي</span>
                                        @else
                                            <span class="badge bg-danger">📕 دين</span>
                                        @endif
                                    </td>
                                    <td class="text-muted font-monospace">{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                       <div class="d-flex gap-1 justify-content-center flex-nowrap">
                                            <button wire:click="showSaleDetails({{ $sale->id }})" class="btn btn-primary btn-sm px-2 py-0 fw-bold text-white text-xs shadow-sm">👁️ كشف</button>
                                            <button wire:click="editAndLoadToCart({{ $sale->id }})" class="btn btn-warning btn-sm px-2 py-0 fw-bold text-dark text-xs shadow-sm">✏️ تعديل</button>
                                            <button onclick="confirm('إلغاء الفاتورة؟') || event.stopImmediatePropagation()" wire:click="voidSale({{ $sale->id }})" class="btn btn-outline-danger btn-sm px-2 py-0 text-xs">🗑️ إلغاء</button>
                                       </div>                         
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted p-4">لا توجد فواتير مطابقة للبحث.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- ✅ جدول المجموع الكلي الواضح والمنظم --}}
                @if($salesList->isNotEmpty())
                <div class="p-3 bg-light border-top">
                    <h6 class="fw-bold text-dark mb-2 text-center">💰 المجموع الكلي (حسب الفلتر المطبق)</h6>
                    <table class="table table-sm table-bordered table-hover mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>إجمالي المبيعات</th>
                                <th>المحصّل (مدفوع)</th>
                                <th>الديون المترتبة</th>
                                <th>رأس المال</th>
                                <th>صافي الأرباح</th>
                            </tr>
                        </thead>
                        <tbody class="font-monospace fw-bold">
                            <tr>
                                <td class="text-success">{{ number_format($total_sum, 2) }} دج</td>
                                <td class="text-primary">{{ number_format($total_paid, 2) }} دج</td>
                                <td class="text-danger">{{ number_format($total_debt, 2) }} دج</td>
                                <td class="text-secondary">{{ number_format($total_capital, 2) }} دج</td>
                                <td class="text-success bg-success-subtle">{{ number_format($total_profit, 2) }} دج</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="card-footer bg-white pt-2">
                    {{ $salesList->links() }}
                </div>
            </div>
        </div>

        <!-- القسم الأيسر: كاشف ومستعرض السلع المبيوعة داخل الفاتورة المحددة -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-dark text-white py-3">
                    <h6 class="fw-bold mb-0">📦 تفاصيل المقبوضات والسلع</h6>
                </div>
                <div class="card-body">
                    @if($selected_sale)
                        <div class="alert alert-secondary p-2 small mb-3">
                            <div class="row">
                                <div class="col-6"><b>الفاتورة:</b> #{{ $selected_sale->id }}</div>
                                <div class="col-6 text-end"><b>الزبون:</b> {{ $selected_sale->customer_id ? optional($selected_sale->customer)->name : 'عابر' }}</div>
                                <div class="col-12 mt-1"><b>التاريخ:</b> {{ $selected_sale->created_at->format('Y-m-d H:i:s') }}</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered text-center align-middle small mb-3">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">اسم السلعة / الكتاب</th>
                                        <th>الكمية</th>
                                        <th>السعر</th>
                                        <th>المجموع</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selected_sale->items as $item)
                                        <tr>
                                            <td class="text-start fw-bold text-dark">{{ $item->product_name }}</td>
                                            <td class="font-monospace fw-bold">{{ $item->quantity }}</td>
                                            <td class="font-monospace">{{ number_format($item->unit_price, 2) }}</td>
                                            <td class="font-monospace fw-bold text-secondary">{{ number_format($item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-light p-2 border rounded small font-monospace">
                            <div class="d-flex justify-content-between"><span>الصافي المطلوب:</span><b class="text-danger">{{ number_format($selected_sale->total_amount, 2) }} دج</b></div>
                            <div class="d-flex justify-content-between mt-1"><span>الكاش المدفوع فعلياً:</span><b class="text-success">{{ number_format($selected_sale->paid_amount, 2) }} دج</b></div>
                            
                            {{-- ✅ عرض حسابات رأس المال والأرباح للفاتورة المفتوحة --}}
                            <div class="d-flex justify-content-between mt-1 text-secondary"><span>رأس المال (المقدر):</span><b>{{ number_format($selected_sale_capital, 2) }} دج</b></div>
                            <div class="d-flex justify-content-between mt-1 border-top pt-1"><span class="fw-bold text-success">صافي الربح المتوقع:</span><b class="text-success fw-bold">{{ number_format($selected_sale_profit, 2) }} دج</b></div>

                            @php $rest = $selected_sale->total_amount - $selected_sale->paid_amount; @endphp
                            @if($rest > 0)
                                <div class="d-flex justify-content-between mt-1 border-top pt-1 text-danger fw-bold"><span>⚠️ المتبقي في سجل الديون:</span><span>{{ number_format($rest, 2) }} دج</span></div>
                            @endif
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            ♻️ اضغط على زر <b>"👁️ كشف"</b> بجانب أي فاتورة لتظهر لك هنا تفاصيل السلع المشتراة ومؤشراتها المالية فوراً!
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>