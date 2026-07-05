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
    public $selected_sale = null; // لحفظ الفاتورة المراد عرض تفاصيل سلعها

    // دالة فتح تفاصيل الفاتورة لرؤية السلع السابقة
    public function showSaleDetails($id)
    {
        $this->selected_sale = Sale::with('items')->findOrFail($id);
    }

    // 🗑️ دالة إلغاء الفاتورة بالكامل (إرجاع السلع للمخزن وحذف سطر الدين)
    public function voidSale($id)
    {
        DB::beginTransaction();
        try {
            $sale = Sale::with('items')->findOrFail($id);

            // 1. إعادة السلع المبيوعة إلى المخزن تلقائياً (current_stock)
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)->increment('current_stock', $item->quantity);
                }
            }

            // 2. إذا كانت الفاتورة ولدت سطر دين، نقوم بحذفه من جدول الزبائن لكي لا يطالب به
            if (in_array($sale->payment_method, ['partial', 'debt']) && $sale->customer_id) {
                // البحث عن سطر الدين المطابق لرقم الفاتورة في جدول الزبائن وحذفه
                Customer::where('name', optional($sale->customer)->name)
                        ->where('observation', 'like', '%فاتورة رقم #' . $sale->id . '%')
                        ->delete();
            }

            // 3. حذف الفاتورة وعناصرها من النظام
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

        // 1. إرجاع الكميات القديمة للمخزن مؤقتاً لتجنب تضارب الجرد
        foreach ($sale->items as $item) {
            if ($item->product_id) {
                Product::where('id', $item->product_id)->increment('current_stock', $item->quantity);
            }
        }

        // 2. إذا كان هناك دين مسجل في جدول الزبائن لهذه الفاتورة، نحذفه لأنه سيتغير بناءً على التعديل الجديد
        if (in_array($sale->payment_method, ['partial', 'debt']) && $sale->customer_id) {
            Customer::where('name', optional($sale->customer)->name)
                    ->where('observation', 'like', '%فاتورة رقم #' . $sale->id . '%')
                    ->delete();
        }

        // 3. تجهيز بيانات السلة لشحنها في شاشة الـ POS
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

        // 4. حفظ البيانات في الـ Session لنقلها فوراً لشاشة الـ POS
        session()->put('edit_sale_id', $sale->id);
        session()->put('edit_cart', $cartdata);
        session()->put('edit_customer_id', $sale->customer_id);
        session()->put('edit_discount', $sale->discount_amount);
        session()->put('edit_payment_method', $sale->payment_method);
        session()->put('edit_paid_amount', $sale->paid_amount);

        // 5. حذف الفاتورة القديمة لأن الفاتورة المعدلة ستأخذ مكانها بنجاح
        $sale->items()->delete();
        $sale->delete();

        DB::commit();

        // 6. توجيه الكاشير فوراً لشاشة الـ POS والسلة مشحونة وجاهزة للتعديل!
        return redirect()->route('pos.index'); // تأكد أن هذا اسم مسار صفحة الـ POS لديك

    } catch (\Exception $e) {
        DB::rollBack();
        session()->flash('error', 'حدث خطأ أثناء تحميل الفاتورة للتعديل: ' . $e->getMessage());
    }
}
    public function rendering()
    {
        if ($this->search) { $this->resetPage(); }
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
        <!-- الجدول الأيمن: أرشيف الفواتير والمبيعات السابقة -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-dark mb-2">🔍 أرشيف مبيعات مكتبة السلام والبحث عن السلع السابقة</h6>
                    <input type="text" wire:model.live="search" class="form-control form-control-sm w-70" placeholder="ابحث باسم الزبون، رقم الفاتورة، أو طريقة الدفع...">
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
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
                            @php
                                // جلب الفواتير مع البحث باسم الزبون المرتبط بها
                                $salesList = Sale::with('customer')
                                    ->where(function($query) {
                                        $query->where('id', 'like', '%'.$this->search.'%')
                                              ->orWhere('payment_method', 'like', '%'.$this->search.'%')
                                              ->orWhereHas('customer', function($q) {
                                                  $q->where('name', 'like', '%'.$this->search.'%');
                                              });
                                    })
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(10);
                            @endphp

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
                                       <div class="d-flex gap-1 justify-content-center">
    <button wire:click="showSaleDetails({{ $sale->id }})" class="btn btn-primary btn-sm px-1 py-0 fw-bold text-white text-xs shadow-sm">
        👁️ كشف
    </button>
    
    <button wire:click="editAndLoadToCart({{ $sale->id }})" class="btn btn-warning btn-sm px-1 py-0 fw-bold text-dark text-xs shadow-sm">
        ✏️ تعديل
    </button>
    
    <button onclick="confirm('إلغاء الفاتورة؟') || event.stopImmediatePropagation()" wire:click="voidSale({{ $sale->id }})" class="btn btn-outline-danger btn-sm px-1 py-0 text-xs">
        🗑️ إلغاء
    </button>
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
                <div class="card-footer bg-white pt-2">
                    {{ $salesList->links() }}
                </div>
            </div>
        </div>

        <!-- القسم الأيسر: كاشف ومستعرض السلع المبيوعة داخل الفاتورة المحددة -->
        <div class="col-md-5">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-dark text-white py-3">
                    <h6 class="fw-bold mb-0">📦 تفاصيل المقبوضات والسلع المشتراة سابقاً</h6>
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

                        <div class="bg-light p-2 border rounded small font-monospace">
                            <div class="d-flex justify-content-between"><span>الصافي المطلوب:</span><b class="text-danger">{{ number_format($selected_sale->total_amount, 2) }} دج</b></div>
                            <div class="d-flex justify-content-between mt-1"><span>الكاش المدفوع فعلياً:</span><b class="text-success">{{ number_format($selected_sale->paid_amount, 2) }} دج</b></div>
                            @php $rest = $selected_sale->total_amount - $selected_sale->paid_amount; @endphp
                            @if($rest > 0)
                                <div class="d-flex justify-content-between mt-1 border-top pt-1 text-danger fw-bold"><span>⚠️ المتبقي في سجل الديون:</span><span>{{ number_format($rest, 2) }} دج</span></div>
                            @endif
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            ♻️ اضغط على زر <b>"👁️ كشف السلع"</b> بجانب أي فاتورة لتظهر لك هنا تفاصيل السلع المشتراة ومؤشراتها المالية فوراً!
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>