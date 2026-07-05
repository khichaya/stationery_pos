<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $barcode = '';
    public $suggestions = []; // 🆕 نتائج الاقتراح اللحظي أثناء الكتابة
    public $highlightedIndex = -1; // 🆕 مؤشر العنصر المظلل حالياً بالأسهم
    public $cart = [];
    public $customers = [];
    public $selected_customer_id = null;
    public $discount_amount = 0;
    public $paid_amount = 0;
    public $payment_method = 'full'; // full | partial | debt

    public $total_amount = 0;
    public $final_total = 0;

    // إضافة سلعة يدوية (غير مسجلة بالمخزن)
    public $quick_item_name = '';
    public $quick_item_price = 0;
    public $quick_item_qty = 1;

    // بيانات آخر فاتورة محفوظة (للطباعة)
    public $last_sale = null;

    public function mount()
    {
        $this->customers = Customer::all();

        // 🚀 التحديث: إذا كان الكاشير قادماً من صفحة الأرشيف بغرض التعديل، يتم شحن السلة فوراً هنا!
        if (session()->has('edit_cart')) {
            $this->cart = session()->get('edit_cart');
            $this->selected_customer_id = session()->get('edit_customer_id');
            $this->discount_amount = session()->get('edit_discount');
            $this->payment_method = session()->get('edit_payment_method');
            $this->paid_amount = session()->get('edit_paid_amount');

            $this->calculateTotals();

            session()->forget(['edit_cart', 'edit_customer_id', 'edit_discount', 'edit_payment_method', 'edit_paid_amount']);

            session()->flash('success', '🔄 تم شحن الفاتورة السابقة في السلة بنجاح! يمكنك الآن حذف سلع، تعديل كميات، أو إضافة سلع جديدة ثم الحفظ.');
        }
    }

    // 🆕 يُستدعى تلقائياً من Livewire في كل مرة يتغير فيها $barcode أثناء الكتابة
    public function updatedBarcode()
    {
        $term = trim($this->barcode);

        if ($term === '') {
            $this->suggestions = [];
            $this->highlightedIndex = -1;
            return;
        }

        $this->suggestions = Product::where('name', 'like', "%{$term}%")
            ->orWhere('barcode', 'like', "%{$term}%")
            ->orWhere('box_barcode', 'like', "%{$term}%")
            ->limit(8)
            ->get(['id', 'name', 'barcode', 'price_1', 'current_stock'])
            ->toArray();

        // 🆕 نُظلّل أول عنصر تلقائياً حتى يمكن الإضافة بـ Enter مباشرة بدون أسهم إن أراد المستخدم
        $this->highlightedIndex = count($this->suggestions) > 0 ? 0 : -1;
    }

    // 🆕 التنقل لأسفل في قائمة الاقتراحات
    public function highlightNext()
    {
        if (count($this->suggestions) === 0) return;
        $this->highlightedIndex = min($this->highlightedIndex + 1, count($this->suggestions) - 1);
    }

    // 🆕 التنقل لأعلى في قائمة الاقتراحات
    public function highlightPrev()
    {
        if (count($this->suggestions) === 0) return;
        $this->highlightedIndex = max($this->highlightedIndex - 1, 0);
    }

    // 🆕 يُستدعى عند الضغط على سلعة من قائمة الاقتراحات مباشرة بالماوس (اختياري، بجانب الأسهم)
    public function selectSuggestion($productId)
    {
        $product = Product::find($productId);

        if ($product) {
            $this->barcode = '';
            $this->addToCart($product);
        }

        $this->suggestions = [];
        $this->highlightedIndex = -1;
        $this->barcode = '';
    }

    public function scanBarcode()
    {
        // 🆕 دوبل إنتر = بيع مباشر: لو الحقل فاضي أصلاً ومفيش اقتراحات، يبقى المستخدم ضغط
        // Enter ثانية بعد ما فرّغ الحقل تلقائياً من إضافة سابقة -> ننفّذ البيع فوراً
        if (empty($this->barcode) && count($this->suggestions) === 0) {
            $this->checkout();
            return;
        }

        // 🆕 لو فيه قائمة اقتراحات ظاهرة وعنصر مظلل، Enter تضيفه مباشرة (بدون حاجة لضغط مزدوج)
        if (count($this->suggestions) > 0 && $this->highlightedIndex >= 0) {
            $selected = $this->suggestions[$this->highlightedIndex] ?? null;

            if ($selected) {
                $product = Product::find($selected['id']);
                if ($product) {
                    $this->barcode = '';
                    $this->addToCart($product);
                }
            }

            $this->suggestions = [];
            $this->highlightedIndex = -1;
            $this->barcode = '';
            return;
        }

        if (empty($this->barcode)) return;

        // احتياطي: بحث دقيق عن الباركود التام (حالة عدم ظهور أي اقتراحات لأي سبب)
        $product = Product::where('barcode', $this->barcode)
                          ->orWhere('box_barcode', $this->barcode)
                          ->first();

        if ($product) {
            $this->addToCart($product);
        } else {
            session()->flash('error', 'السلعة غير موجودة! يمكنك إضافتها يدوياً من الزر أدناه.');
        }

        $this->barcode = '';
        $this->suggestions = [];
        $this->highlightedIndex = -1;
    }

    public function addToCart(Product $product)
    {
        $key = 'p_' . $product->id;

        $price = $product->price_1;
        $name = $product->name;
        $qtyToIncrement = 1;

        if ($this->barcode === $product->box_barcode && !empty($product->box_barcode)) {
            $price = $product->price_3 ?: $product->price_1;
            $name .= ' (علبة × ' . $product->package_items_count . ')';
            $qtyToIncrement = $product->package_items_count ?: 1;
        }

        if (array_key_exists($key, $this->cart)) {
            $this->cart[$key]['quantity'] += $qtyToIncrement;
            $this->cart[$key]['subtotal'] = $this->cart[$key]['quantity'] * $this->cart[$key]['price'];
        } else {
            $this->cart[$key] = [
                'key' => $key,
                'product_id' => $product->id,
                'is_custom' => false,
                'name' => $name,
                'price' => $price,
                'quantity' => $qtyToIncrement,
                'subtotal' => $price * $qtyToIncrement,
            ];
        }

        $this->calculateTotals();
    }

    public function addQuickItem()
    {
        $this->validate([
            'quick_item_name' => 'required|string|max:255',
            'quick_item_price' => 'required|numeric|min:0',
            'quick_item_qty' => 'required|integer|min:1',
        ], [
            'quick_item_name.required' => 'يرجى كتابة اسم السلعة أو الخدمة.',
            'quick_item_price.required' => 'يرجى إدخال السعر.',
        ]);

        $key = 'c_' . uniqid();

        $this->cart[$key] = [
            'key' => $key,
            'product_id' => null,
            'is_custom' => true,
            'name' => $this->quick_item_name,
            'price' => $this->quick_item_price,
            'quantity' => $this->quick_item_qty,
            'subtotal' => $this->quick_item_price * $this->quick_item_qty,
        ];

        $this->calculateTotals();

        $this->reset(['quick_item_name', 'quick_item_price', 'quick_item_qty']);
        $this->quick_item_qty = 1;

        $this->dispatch('close-modal', modalId: 'quickItemModal');
    }

    // 🆕 الآن تُستدعى في كل ضغطة زر (wire:input) بدل الانتظار للخروج من الخانة (wire:change)
    public function updateQuantity($key, $qty)
    {
        if (!array_key_exists($key, $this->cart)) return;

        $qty = (int) $qty;
        if ($qty < 1) $qty = 1;

        $this->cart[$key]['quantity'] = $qty;
        $this->cart[$key]['subtotal'] = $qty * $this->cart[$key]['price'];

        $this->calculateTotals();
    }

    public function removeFromCart($key)
    {
        unset($this->cart[$key]);
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->total_amount = array_sum(array_column($this->cart, 'subtotal'));
        $this->discount_amount = min($this->discount_amount, $this->total_amount);
        $this->final_total = $this->total_amount - $this->discount_amount;

        if ($this->payment_method === 'full') {
            $this->paid_amount = $this->final_total;
        } elseif ($this->payment_method === 'debt') {
            $this->paid_amount = 0;
        }
    }

    public function updatedPaymentMethod()
    {
        $this->calculateTotals();
    }

    public function checkout()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'السلة فارغة!');
            return;
        }

        if (in_array($this->payment_method, ['partial', 'debt']) && !$this->selected_customer_id) {
            session()->flash('error', 'يجب اختيار الزبون عند وجود دين (دفع جزئي أو دين كامل).');
            return;
        }

        if ($this->payment_method === 'partial' && $this->paid_amount >= $this->final_total) {
            session()->flash('error', 'المبلغ المدفوع في "الدفع الجزئي" يجب أن يكون أقل من الإجمالي.');
            return;
        }

        DB::beginTransaction();
        try {
            $sale = Sale::create([
                'customer_id' => $this->selected_customer_id ?: null,
                'user_id' => auth()->id() ?? 1,
                'total_amount' => $this->final_total,
                'paid_amount' => $this->paid_amount,
                'discount_amount' => $this->discount_amount,
                'payment_method' => $this->payment_method,
            ]);

            foreach ($this->cart as $item) {
                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                if ($item['product_id']) {
                    Product::find($item['product_id'])->decrement('current_stock', $item['quantity']);
                }
            }

            $debt = $this->final_total - $this->paid_amount;

            if ($debt > 0 && $this->selected_customer_id) {
                $originalCustomer = Customer::find($this->selected_customer_id);

                if ($originalCustomer) {
                    Customer::create([
                        'name' => $originalCustomer->name,
                        'phone' => $originalCustomer->phone,
                        'total_debt' => $debt,
                        'observation' => 'مشتريات فاتورة رقم #' . $sale->id,
                    ]);
                }
            }

            DB::commit();

            $setting = InstitutionSetting::first();

            $logoBase64 = '';
            if ($setting && $setting->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($setting->logo_path)) {
                $logodata = \Illuminate\Support\Facades\Storage::disk('public')->get($setting->logo_path);
                $mimeType = mime_content_type(\Illuminate\Support\Facades\Storage::disk('public')->path($setting->logo_path));
                $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logodata);
            }

            $customerName = $this->selected_customer_id
                ? optional(Customer::find($this->selected_customer_id))->name
                : 'زبون عابر';

            $this->last_sale = [
                'id' => $sale->id,
                'date' => now()->format('Y-m-d H:i'),
                'customer' => $customerName,
                'items' => array_values($this->cart),
                'total_amount' => $this->total_amount,
                'discount_amount' => $this->discount_amount,
                'final_total' => $this->final_total,
                'paid_amount' => $this->paid_amount,
                'debt' => max(0, $debt),
                'payment_method' => $this->payment_method,
                'company_name' => $setting->name ?? 'مكتبة السلام',
                'company_phone' => $setting->phone ?? '',
                'company_address' => $setting->address ?? '',
                'company_email' => $setting->email ?? '',
                'company_nif' => $setting->nif ?? '',
                'company_nis' => $setting->nis ?? '',
                'company_rc' => $setting->rc ?? '',
                'company_ai' => $setting->ai ?? '',
                'company_footer' => $setting->invoice_footer ?? '',
                'company_logo' => $setting->logo_path ?? '',
                'company_logo_base64' => $logoBase64,
            ];

            $this->reset(['cart', 'total_amount', 'final_total', 'discount_amount', 'paid_amount', 'selected_customer_id', 'payment_method']);
            $this->payment_method = 'full';

            session()->flash('success', 'تم حفظ الفاتورة بنجاح وترحيل المستحقات الماليّة لجداول الديون!');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'حدث خطأ أثناء معالجة العملية: ' . $e->getMessage());
        }
    }
};
?>

<div>
    <div class="row">
        @if (session()->has('success'))
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show fw-bold">✨ {{ session('success') }}</div>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show fw-bold">⚠️ {{ session('error') }}</div>
            </div>
        @endif

        {{-- إشعار الفاتورة الأخيرة + أزرار الطباعة --}}
        @if ($last_sale)
            <div class="col-12">
                <div class="card border-success mb-3 shadow-sm">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                        <div class="small">
                            🧾 فاتورة محفوظة بنجاح <b>#{{ $last_sale['id'] }}</b> — الإجمالي الحركي:
                            <b class="text-success">{{ number_format($last_sale['final_total'], 2) }} دج</b>
                            @if($last_sale['debt'] > 0)
                                <span class="badge bg-danger ms-1">تم قيد دين تلقائي: {{ number_format($last_sale['debt'], 2) }} دج للزبون</span>
                            @endif
                        </div>
                        <div class="d-flex gap-1">
                            <span class="small text-muted align-self-center ms-1">🖨️ طباعة الفاتورة الفورية:</span>
                            <button onclick="printInvoice('A4')" class="btn btn-sm btn-outline-dark fw-bold">A4</button>
                            <button onclick="printInvoice('A5')" class="btn btn-sm btn-outline-dark fw-bold">A5</button>
                            <button onclick="printInvoice('A6')" class="btn btn-sm btn-warning fw-bold">A6 (تيكيت كاشير)</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-md-7">
            <div class="card mb-3 shadow-sm">
                <div class="card-body bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="mb-0 fw-bold text-dark">🔍 مسح وقراءة الباركود (امسح قطعة أو كرتونة واضغط Enter)</label>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#quickItemModal">
                            ➕ خدمة / مبيعات سريعة يدوية
                        </button>
                    </div>

                    {{-- 🆕 حاوية نسبية لعرض قائمة الاقتراحات أسفل حقل البحث --}}
                    <div class="position-relative">
                        <input type="text"
                               wire:model.live="barcode"
                               wire:keydown.enter="scanBarcode"
                               wire:keydown.arrow-down.prevent="highlightNext"
                               wire:keydown.arrow-up.prevent="highlightPrev"
                               class="form-control form-control-lg text-start font-monospace"
                               placeholder="امسح بالليزر أو اكتب هنا للبحث السريع..."
                               autocomplete="off"
                               autofocus>

                        {{-- 🆕 قائمة الاقتراحات اللحظية - تنقل بالأسهم (↑ ↓) وإضافة بـ Enter --}}
                        @if (count($suggestions) > 0)
                            <div class="list-group position-absolute w-100 shadow-lg" style="z-index: 1050; max-height: 320px; overflow-y: auto;">
                                @foreach ($suggestions as $index => $product)
                                    <button type="button"
                                            wire:click="selectSuggestion({{ $product['id'] }})"
                                            wire:mouseenter="$set('highlightedIndex', {{ $index }})"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center text-start {{ $index === $highlightedIndex ? 'active' : '' }}">
                                        <span>
                                            <span class="fw-bold {{ $index === $highlightedIndex ? '' : 'text-dark' }}">{{ $product['name'] }}</span>
                                            @if ($product['barcode'])
                                                <span class="small font-monospace ms-2 {{ $index === $highlightedIndex ? 'text-white-50' : 'text-muted' }}">#{{ $product['barcode'] }}</span>
                                            @endif
                                        </span>
                                        <span class="d-flex align-items-center gap-2">
                                            <span class="badge {{ $index === $highlightedIndex ? 'bg-light text-dark' : 'bg-secondary-subtle text-dark' }} font-monospace">{{ number_format($product['price_1'], 2) }} دج</span>
                                            <span class="badge {{ $product['current_stock'] > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                مخزون: {{ $product['current_stock'] }}
                                            </span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <p class="text-muted small mt-1 mb-0">💡 اكتب اسم أو رمز السلعة، تنقّل بالأسهم <kbd>↑</kbd> <kbd>↓</kbd> بين النتائج، ثم اضغط <kbd>Enter</kbd> لإضافة العنصر المظلل مباشرة للسلة.</p>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white text-center py-3">
                    <h5 class="mb-0 fw-bold">🛒 سلة المبيعات الجارية</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover text-center align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">السلعة</th>
                                <th>السعر</th>
                                <th>الكمية</th>
                                <th>المجموع</th>
                                <th>حذف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cart as $key => $item)
                                <tr class="{{ $item['is_custom'] ? 'table-warning' : '' }}">
                                    <td class="text-start fw-bold text-dark">
                                        {{ $item['name'] }}
                                        @if($item['is_custom'])
                                            <span class="badge bg-warning text-dark text-xs ms-1">يدوي</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace">{{ number_format($item['price'], 2) }}</td>
                                    <td style="max-width:85px">
                                        {{-- 🆕 wire:input بدل wire:change: يحسب الإجمالي أثناء الكتابة مباشرة وليس بعد الخروج من الخانة --}}
                                        <input type="number"
                                               value="{{ $item['quantity'] }}"
                                               wire:input.debounce.150ms="updateQuantity('{{ $key }}', $event.target.value)"
                                               class="form-control form-control-sm text-center font-monospace" min="1">
                                    </td>
                                    <td class="fw-bold font-monospace">{{ number_format($item['subtotal'], 2) }}</td>
                                    <td>
                                        <button wire:click="removeFromCart('{{ $key }}')" class="btn btn-sm btn-outline-danger p-0 px-2">×</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted p-3">السلة فارغة، قم بمسح السلع للبدء.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <hr class="my-3">

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small fw-bold text-secondary">👤 حساب الزبون المشتري:</label>
                            <select wire:model="selected_customer_id" class="form-select form-select-sm fw-bold">
                                <option value="">-- زبون عابر (نقدي) --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-secondary">📉 قيمة التخفيض الفوري (دج):</label>
                            <input type="number" wire:model.live="discount_amount" wire:change="calculateTotals" class="form-control form-control-sm font-monospace text-center fw-bold text-danger" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold d-block text-secondary mb-1">💳 هندسة طريقة التحصيل والبيع:</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="payment_method" id="pay_full" value="full" wire:model.live="payment_method">
                            <label class="btn btn-outline-success btn-sm fw-bold" for="pay_full">💵 دفع كامل</label>

                            <input type="radio" class="btn-check" name="payment_method" id="pay_partial" value="partial" wire:model.live="payment_method">
                            <label class="btn btn-outline-warning btn-sm fw-bold" for="pay_partial">➗ دفع جزئي</label>

                            <input type="radio" class="btn-check" name="payment_method" id="pay_debt" value="debt" wire:model.live="payment_method">
                            <label class="btn btn-outline-danger btn-sm fw-bold" for="pay_debt">📕 دين كامل (كريدي)</label>
                        </div>
                        @if(in_array($payment_method, ['partial', 'debt']) && !$selected_customer_id)
                            <div class="text-danger small mt-2 fw-bold animate-pulse">⚠️ نظام الأمان: يجب تحديد كرت حساب الزبون لترحيل هذا الدين إليه!</div>
                        @endif
                    </div>

                    <div class="row mb-3 align-items-center">
                        <div class="col-6">
                            <label class="small fw-bold text-success">المستلم نقداً كاش:</label>
                            <input type="number"
                                   wire:model.live="paid_amount"
                                   class="form-control form-control-lg fw-bold text-success text-center font-monospace"
                                   min="0"
                                   {{ $payment_method !== 'partial' ? 'disabled' : '' }}>
                        </div>
                        <div class="col-6 text-end">
                            <div class="bg-dark text-white rounded-3 p-3 text-center">
                                <span class="small text-white-50 d-block mb-1">الصافي المطلوب</span>
                                <h2 class="fw-bold mb-0 font-monospace text-nowrap" style="font-size: 2rem; color: #00ff88; text-shadow: 0 0 10px rgba(0,255,136,0.3);">
                                    {{ number_format($final_total, 2) }}
                                    <span style="font-size: 1.1rem;">دج</span>
                                </h2>
                                @if($payment_method !== 'full')
                                    <span class="badge bg-danger font-monospace mt-2" style="font-size: 0.9rem;">
                                        دين: {{ number_format(max(0, $final_total - $paid_amount), 2) }} دج
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <button wire:click="checkout" class="btn btn-primary w-100 btn-lg fw-bold py-2 shadow-sm">
                        💾 ⚡ إنهاء الفاتورة والترحيل الفوري
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- نافذة إضافة سلعة يدوية --}}
    <div wire:ignore.self class="modal fade" id="quickItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="fw-bold mb-0">➕ إضافة سلعة سريعة بالفاتورة</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">البيان / الخدمة <span class="text-danger">*</span></label>
                        <input type="text" wire:model="quick_item_name" class="form-control form-control-sm" placeholder="مثال: فوتوكوبي مستندات">
                        @error('quick_item_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">السعر المفرد</label>
                            <input type="number" step="0.01" wire:model="quick_item_price" class="form-control form-control-sm text-center font-monospace" min="0">
                            @error('quick_item_price') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">الكمية المطلوبة</label>
                            <input type="number" wire:model="quick_item_qty" class="form-control form-control-sm text-center font-monospace" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" wire:click="addQuickItem" class="btn btn-primary btn-sm fw-bold">إدراج بالسلة</button>
                </div>
            </div>
        </div>
    </div>

    @if ($last_sale)
        <script id="last-sale-data" type="application/json">
            {!! json_encode($last_sale, JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('close-modal', (event) => {
                const modalElement = document.getElementById(event.modalId);
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) { modalInstance.hide(); }
            });
        });
    </script>

    {{-- 🆕 سكربت الطباعة نُقل لملف خارجي منفصل لتقليل حجم هذا الملف --}}
    <script src="{{ asset('js/print-invoice.js') }}"></script>
</div>