<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Favorite;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

new class extends Component
{
    public $barcode = '';
    public $suggestions = [];
    public $highlightedIndex = -1;
    public $cart = [];
    public $customers = [];
    public $selected_customer_id = null;
    public $discount_amount = 0;
    public $paid_amount = 0;
    public $payment_method = 'full';
    public $suspendedCarts = [];
    public $total_amount = 0;
    public $final_total = 0;

    // اضافة سلعة يدوية
    public $quick_item_name = '';
    public $quick_item_price = 0;
    public $quick_item_qty = 1;

    // بيانات اخر فاتورة
    public $last_sale = null;

    // خصائص الارباح
    public $total_capital = 0;
    public $total_profit = 0;
    public $profit_details = [];

    // ═══════════════════════════════════════════════
    // خصائص نظام المفضلات
    // ═══════════════════════════════════════════════
    public $favorites = [];
    public $showFavoritesManager = false;

    // نموذج اضافة/تعديل مفضلة
    public $fav_id = null;
    public $fav_name = '';
    public $fav_price = 0;
    public $fav_icon = '📦';
    public $fav_color = '#872061';
    public $fav_sort_order = 0;

    public function mount()
    {
        $this->customers = Customer::all();
        $this->loadFavorites();
         $this->loadSuspendedCarts();
        if (session()->has('edit_cart')) {
            $this->cart = session()->get('edit_cart');
            $this->selected_customer_id = session()->get('edit_customer_id');
            $this->discount_amount = session()->get('edit_discount');
            $this->payment_method = session()->get('edit_payment_method');
            $this->paid_amount = session()->get('edit_paid_amount');

            foreach ($this->cart as $key => &$item) {
                $item['is_favorite'] = $item['is_favorite'] ?? false;
                $item['is_custom'] = $item['is_custom'] ?? false;
                $item['favorite_id'] = $item['favorite_id'] ?? null;
            }
            unset($item);

            $this->calculateTotals();

            session()->forget(['edit_cart', 'edit_customer_id', 'edit_discount', 'edit_payment_method', 'edit_paid_amount']);

            session()->flash('success', '🔄 تم شحن الفاتورة السابقة في السلة بنجاح! يمكنك الان حذف سلع، تعديل كميات، او اضافة سلع جديدة ثم الحفظ.');
        }
    }

    // ═══════════════════════════════════════════════
    // دوال نظام المفضلات
    // ═══════════════════════════════════════════════

    public function loadFavorites()
    {
        $this->favorites = Favorite::activeOrdered();
    }

    public function addFavoriteToCart($favoriteId)
    {
        $favorite = Favorite::find($favoriteId);

        if (!$favorite) {
            session()->flash('error', 'العنصر المفضل غير موجود!');
            return;
        }

        $key = 'f_' . $favorite->id . '_' . uniqid();

        $this->cart[$key] = [
            'key' => $key,
            'product_id' => null,
            'is_custom' => true,
            'is_favorite' => true,
            'favorite_id' => $favorite->id,
            'name' => $favorite->name,
            'price' => $favorite->price,
            'quantity' => 1,
            'subtotal' => $favorite->price,
        ];

        $this->calculateTotals();

        session()->flash('success', '✨ تم اضافة "' . $favorite->name . '" من المفضلات للسلة!');
    }

    public function toggleFavoritesManager()
    {
        $this->showFavoritesManager = !$this->showFavoritesManager;
        if ($this->showFavoritesManager) {
            $this->resetFavoriteForm();
        }
    }

    public function resetFavoriteForm()
    {
        $this->fav_id = null;
        $this->fav_name = '';
        $this->fav_price = 0;
        $this->fav_icon = '📦';
        $this->fav_color = '#872061';
        $this->fav_sort_order = 0;
    }

    public function editFavorite($id)
    {
        $favorite = Favorite::find($id);
        if (!$favorite) return;

        $this->fav_id = $favorite->id;
        $this->fav_name = $favorite->name;
        $this->fav_price = $favorite->price;
        $this->fav_icon = $favorite->icon;
        $this->fav_color = $favorite->color;
        $this->fav_sort_order = $favorite->sort_order;
        $this->showFavoritesManager = true;
    }

    public function saveFavorite()
    {
        $this->validate([
            'fav_name' => 'required|string|max:255',
            'fav_price' => 'required|numeric|min:0',
            'fav_icon' => 'nullable|string|max:10',
            'fav_color' => 'required|string|max:7',
            'fav_sort_order' => 'nullable|integer|min:0',
        ], [
            'fav_name.required' => 'اسم العنصر المفضل مطلوب.',
            'fav_price.required' => 'السعر مطلوب.',
        ]);

        Favorite::updateOrCreate(
            ['id' => $this->fav_id],
            [
                'name' => $this->fav_name,
                'price' => $this->fav_price,
                'icon' => $this->fav_icon ?: '📦',
                'color' => $this->fav_color,
                'sort_order' => $this->fav_sort_order ?: 0,
                'is_active' => true,
            ]
        );

        $this->resetFavoriteForm();
        $this->loadFavorites();

        session()->flash('success', $this->fav_id ? '✏️ تم تحديث العنصر المفضل!' : '➕ تم اضافة عنصر مفضل جديد!');
    }

    public function deleteFavorite($id)
    {
        $favorite = Favorite::find($id);
        if ($favorite) {
            $favorite->delete();
            $this->loadFavorites();
            session()->flash('success', '🗑️ تم حذف العنصر المفضل.');
        }
    }

    // ═══════════════════════════════════════════════
    // دوال البحث والسلة
    // ═══════════════════════════════════════════════

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
            ->orWhereHas('barcodes', function ($q) use ($term) {
                $q->where('barcode', 'like', "%{$term}%");
            })
            ->limit(8)
            ->get(['id', 'name', 'barcode', 'price_1', 'current_stock'])
            ->toArray();

        $this->highlightedIndex = count($this->suggestions) > 0 ? 0 : -1;
    }

    public function highlightNext()
    {
        if (count($this->suggestions) === 0) return;
        $this->highlightedIndex = min($this->highlightedIndex + 1, count($this->suggestions) - 1);
    }

    public function highlightPrev()
    {
        if (count($this->suggestions) === 0) return;
        $this->highlightedIndex = max($this->highlightedIndex - 1, 0);
    }

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
        if (empty($this->barcode) && count($this->suggestions) === 0) {
            $this->checkout();
            return;
        }

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

        $product = Product::where('barcode', $this->barcode)
                    ->orWhere('box_barcode', $this->barcode)
                    ->orWhereHas('barcodes', function ($q) {
                        $q->where('barcode', $this->barcode);
                    })
                    ->first();

        if ($product) {
            $this->addToCart($product);
        } else {
            session()->flash('error', 'السلعة غير موجودة! يمكنك اضافتها يدوياً من الزر ادناه.');
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
                'is_favorite' => false,
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
            'quick_item_name.required' => 'يرجى كتابة اسم السلعة او الخدمة.',
            'quick_item_price.required' => 'يرجى ادخال السعر.',
        ]);

        $key = 'c_' . uniqid();

        $this->cart[$key] = [
            'key' => $key,
            'product_id' => null,
            'is_custom' => true,
            'is_favorite' => false,
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

    // دالة حذف آخر عنصر مضافة للسلة (اختصار Suppr)
    #[On('deleteLastItem')]
    public function deleteLastItem()
    {
        if (!empty($this->cart)) {
            $keys = array_keys($this->cart);
            $lastKey = end($keys);
            unset($this->cart[$lastKey]);
            $this->calculateTotals();
            session()->flash('success', '🗑️ تم حذف آخر عنصر من السلة!');
        }
    }

       // دالة حساب وعرض الأرباح التفصيلية (اختصار F9)
    #[On('showProfits')]
    public function showProfits()
    {
        $this->total_capital = 0;
        $this->total_profit = 0;
        $this->profit_details = [];

        $productIds = array_filter(array_column($this->cart, 'product_id'));
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($this->cart as $item) {
            $cost = 0;
            
            // إذا كان العنصر سلعة مسجلة في المخزن
            if ($item['product_id'] && isset($products[$item['product_id']])) {
                $product = $products[$item['product_id']];
                $cost = $product->cost_price ?? $product->purchase_price ?? 0; 
            } 
            // ✅ إذا كان خدمة سريعة (بدون product_id): نحسب التكلفة 70% والربح 30%
            elseif (empty($item['product_id'])) {
                $cost = $item['price'] * 0.70; // 70% راس مال
            }

            $item_total_cost = $cost * $item['quantity'];
            $item_total_price = $item['price'] * $item['quantity'];
            $item_profit = $item_total_price - $item_total_cost;

            $this->total_capital += $item_total_cost;
            $this->total_profit += $item_profit;

            $this->profit_details[] = [
                'name' => $item['name'],
                'qty' => $item['quantity'],
                'cost' => $cost,
                'price' => $item['price'],
                'profit' => $item_profit
            ];
        }

        $this->dispatch('show-profit-modal');
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

    #[On('shortcut-checkout')]
    public function checkout()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'السلة فارغة!');
            return;
        }

        if (in_array($this->payment_method, ['partial', 'debt']) && !$this->selected_customer_id) {
            session()->flash('error', 'يجب اختيار الزبون عند وجود دين (دفع جزئي او دين كامل).');
            return;
        }

        if ($this->payment_method === 'partial' && $this->paid_amount >= $this->final_total) {
            session()->flash('error', 'المبلغ المدفوع في "الدفع الجزئي" يجب ان يكون اقل من الاجمالي.');
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

            session()->flash('success', 'تم حفظ الفاتورة بنجاح وترحيل المستحقات المالية لجداول الديون!');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'حدث خطأ اثناء معالجة العملية: ' . $e->getMessage());
        }
    }
        // دوال الفواتير المعلقة (Park Sale)
    public function loadSuspendedCarts()
    {
        $this->suspendedCarts = \App\Models\SuspendedCart::where('user_id', auth()->id() ?? 1)
            ->with('customer')->latest()->get();
    }

    public function suspendCurrentCart()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'السلة فارغة، لا يمكن تعليقها!');
            return;
        }
        \App\Models\SuspendedCart::create([
            'user_id' => auth()->id() ?? 1,
            'customer_id' => $this->selected_customer_id ?: null,
            'cart_data' => json_encode($this->cart),
            'discount_amount' => $this->discount_amount,
            'payment_method' => $this->payment_method,
            'paid_amount' => $this->paid_amount,
        ]);
        $this->reset(['cart', 'total_amount', 'final_total', 'discount_amount', 'paid_amount', 'selected_customer_id', 'payment_method']);
        $this->payment_method = 'full';
        $this->loadSuspendedCarts();
        session()->flash('success', '🅿️ تم تعليق الفاتورة بنجاح!');
    }

    public function restoreSuspendedCart($id)
    {
        $suspended = \App\Models\SuspendedCart::find($id);
        if (!$suspended) return;
        $this->cart = json_decode($suspended->cart_data, true);
        $this->selected_customer_id = $suspended->customer_id;
        $this->discount_amount = $suspended->discount_amount;
        $this->payment_method = $suspended->payment_method;
        $this->paid_amount = $suspended->paid_amount;
        $this->calculateTotals();
        $suspended->delete();
        $this->loadSuspendedCarts();
        session()->flash('success', '🔄 تم استرجاع الفاتورة المعلقة!');
    }

    public function deleteSuspendedCart($id)
    {
        \App\Models\SuspendedCart::find($id)?->delete();
        $this->loadSuspendedCarts();
        session()->flash('success', '🗑️ تم إلغاء الفاتورة المعلقة.');
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

        {{-- اشعار الفاتورة الاخيرة + ازرار الطباعة --}}
        @if ($last_sale)
            <div class="col-12">
                <div class="card border-success mb-3 shadow-sm">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                        <div class="small">
                            🧾 فاتورة محفوظة بنجاح <b>#{{ $last_sale['id'] }}</b> — الاجمالي الحركي:
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
                        <label class="mb-0 fw-bold text-dark">🔍 مسح وقراءة الباركود (امسح قطعة او كرتونة واضغط Enter)</label>
                        <div class="d-flex gap-2">
                            <button type="button" id="quick-item-btn" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#quickItemModal" title="اختصار: F11">
                                ➕ خدمة / مبيعات سريعة
                            </button>
                            <button type="button" wire:click="toggleFavoritesManager" class="btn btn-sm btn-outline-danger fw-bold" title="فتح/إغلاق الإدارة">
                                ⭐ ادارة المفضلات
                            </button>
                        </div>
                    </div>

                    <div class="position-relative">
                        <input type="text"
                               id="barcode-input"
                               wire:model.live="barcode"
                               wire:keydown.enter="scanBarcode"
                               wire:keydown.arrow-down.prevent="highlightNext"
                               wire:keydown.arrow-up.prevent="highlightPrev"
                               class="form-control form-control-lg text-start font-monospace"
                               placeholder="امسح بالليزر او اكتب هنا للبحث السريع..."
                               autocomplete="off"
                               autofocus
                               title="اختصار: F10 للعودة هنا">

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
                    <p class="text-muted small mt-1 mb-0">💡 اكتب اسم او رمز السلعة، تنقّل بالاسهم <kbd>↑</kbd> <kbd>↓</kbd> بين النتائج، ثم اضغط <kbd>Enter</kbd> لاضافة العنصر المظلل مباشرة للسلة.</p>
                </div>
            </div>

            {{-- قسم المفضلات --}}
            @if (count($favorites) > 0)
                <div class="card mb-3 shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-danger">
                            ⭐ السلع والخدمات المفضلة (غير المخزنة)
                            <span class="small text-muted fw-normal">— اضغط على المربع لإضافته للسلة فوراً</span>
                        </h6>
                        <span class="badge bg-danger-subtle text-danger">{{ count($favorites) }} عنصر</span>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2">
                            @foreach ($favorites as $fav)
                                <div class="col-6 col-sm-4 col-md-3">
                                    {{-- جعل المربع بالكامل قابل للنقر عبر wire:click --}}
                                    <div wire:click="addFavoriteToCart({{ $fav->id }})" wire:loading.attr="disabled" class="favorite-card" style="--fav-color: {{ $fav->color }}">
                                        <div class="favorite-icon">{{ $fav->icon }}</div>
                                        <div class="favorite-name">{{ $fav->name }}</div>
                                        <div class="favorite-price">{{ number_format($fav->price, 2) }} دج</div>
                                        <div class="click-overlay"><i class="bi bi-plus-lg"></i></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- نافذة ادارة المفضلات --}}
            @if ($showFavoritesManager)
                <div class="card mb-3 shadow-sm border-danger">
                    <div class="card-header bg-danger text-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">⭐ ادارة المفضلات</h6>
                        <button type="button" wire:click="toggleFavoritesManager" class="btn btn-sm btn-light">✕ اغلاق</button>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3 p-2 bg-light rounded-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">الاسم <span class="text-danger">*</span></label>
                                <input type="text" wire:model="fav_name" class="form-control form-control-sm" placeholder="مثال: فوتوكوبي">
                                @error('fav_name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">السعر <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" wire:model="fav_price" class="form-control form-control-sm text-center" min="0">
                                @error('fav_price') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">ايقونة</label>
                                <input type="text" wire:model="fav_icon" class="form-control form-control-sm text-center" placeholder="📦" maxlength="2">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">اللون</label>
                                <input type="color" wire:model="fav_color" class="form-control form-control-sm" style="height: 31px; padding: 2px;">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small fw-bold">الترتيب</label>
                                <input type="number" wire:model="fav_sort_order" class="form-control form-control-sm text-center" min="0">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" wire:click="saveFavorite" class="btn btn-danger btn-sm w-100 fw-bold">
                                    {{ $fav_id ? '✏️ تحديث' : '➕ اضافة' }}
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle text-center small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>الايقونة</th>
                                        <th class="text-start">الاسم</th>
                                        <th>السعر</th>
                                        <th>اللون</th>
                                        <th>الترتيب</th>
                                        <th>الاجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($favorites as $fav)
                                        <tr>
                                            <td>{{ $fav->id }}</td>
                                            <td style="font-size: 1.3rem;">{{ $fav->icon }}</td>
                                            <td class="text-start fw-bold">{{ $fav->name }}</td>
                                            <td class="font-monospace">{{ number_format($fav->price, 2) }} دج</td>
                                            <td>
                                                <span class="color-preview" style="background: {{ $fav->color }};"></span>
                                                <span class="small text-muted">{{ $fav->color }}</span>
                                            </td>
                                            <td>{{ $fav->sort_order }}</td>
                                            <td>
                                                <button type="button" wire:click="editFavorite({{ $fav->id }})" class="btn btn-sm btn-outline-primary py-0 px-2">✏️</button>
                                                <button type="button" wire:click="deleteFavorite({{ $fav->id }})" onclick="return confirm('هل انت متأكد من الحذف؟')" class="btn btn-sm btn-outline-danger py-0 px-2">🗑️</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-muted py-3">لا توجد مفضلات مسجلة بعد</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
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
                                <tr class="{{ ($item['is_custom'] ?? false) ? 'table-warning' : '' }}">
                                    <td class="text-start fw-bold text-dark">
                                        {{ $item['name'] }}
                                        @if($item['is_favorite'] ?? false)
                                            <span class="badge bg-danger text-white text-xs ms-1">⭐ مفضل</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace">{{ number_format($item['price'], 2) }}</td>
                                    <td style="max-width:85px">
                                        <input type="number"
                                               id="qty-input-{{ $loop->index }}"
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
                                    <td colspan="5" class="text-muted p-3">السلة فارغة، قم بمسح السلع او اختر من المفضلات للبدء.</td>
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
                            <input type="number" id="discount-input" wire:model.live="discount_amount" wire:change="calculateTotals" class="form-control form-control-sm font-monospace text-center fw-bold text-danger" min="0">
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
                            <div class="text-danger small mt-2 fw-bold animate-pulse">⚠️ نظام الامان: يجب تحديد كرت حساب الزبون لترحيل هذا الدين اليه!</div>
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

                    <div class="d-flex gap-2 mb-3">
    <button wire:click="checkout" id="checkout-btn" class="btn btn-primary flex-grow-1 btn-lg fw-bold py-2 shadow-sm" title="اختصار: F4">
        💾 ⚡ انهاء الفاتورة
    </button>
    
   <button wire:click="suspendCurrentCart" id="suspend-btn" class="btn btn-warning btn-lg fw-bold py-2 shadow-sm" title="اختصار: F12">
    🅿️ تعليق  
</button>
</div>

{{-- قائمة الفواتير المعلقة --}}
@if(isset($suspendedCarts) && count($suspendedCarts) > 0)
    <div class="mt-3 p-2 border rounded-3 bg-light">
        <h6 class="fw-bold text-dark mb-2 small">📌 فواتير معلقة ({{ count($suspendedCarts) }}):</h6>
        <div class="d-flex flex-wrap gap-2">
            @foreach($suspendedCarts as $susp)
                <div class="p-2 bg-white border rounded shadow-sm d-flex align-items-center gap-2">
                    <div class="small">
                        <b>فاتورة #{{ $susp->id }}</b> <br>
                        <span class="text-muted">{{ $susp->customer ? $susp->customer->name : 'زبون عابر' }}</span>
                    </div>
                    <button wire:click="restoreSuspendedCart({{ $susp->id }})" class="btn btn-success btn-sm py-1 px-2 fw-bold">استرجاع</button>
                    <button wire:click="deleteSuspendedCart({{ $susp->id }})" class="btn btn-outline-danger btn-sm py-1 px-2">إلغاء</button>
                </div>
            @endforeach
        </div>
    </div>
@endif
                    
                    {{-- بانر اختصارات لوحة المفاتيح --}}
                    <div class="alert alert-secondary p-2 small mt-3 d-flex flex-wrap gap-2 justify-content-center align-items-center">
                        <span class="fw-bold">⌨️ اختصارات الكاشير:</span>
                        <span><kbd>F4</kbd> إنهاء</span>
                        <span><kbd>F9</kbd> الأرباح</span>
                        <span><kbd>F10</kbd> باركود</span>
                        <span><kbd>F11</kbd> خدمة سريعة</span>
                        <span><kbd>F12</kbd>تعليق</span>
                        <span><kbd>*</kbd> كمية آخر سلعة</span>
                        <span><kbd>Del</kbd> حذف الأخير</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- نافذة اضافة سلعة سريعة --}}
    <div wire:ignore.self class="modal fade" id="quickItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="fw-bold mb-0">➕ اضافة سلعة سريعة بالفاتورة</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">البيان / الخدمة <span class="text-danger">*</span></label>
                        <input type="text" id="quick-item-name-input" wire:model="quick_item_name" wire:keydown.enter="$nextTick(() => document.getElementById('quick-item-price-input').focus())" class="form-control form-control-sm" placeholder="مثال: فوتوكوبي مستندات">
                        @error('quick_item_name') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">السعر المفرد</label>
                            <input type="number" id="quick-item-price-input" step="0.01" wire:model="quick_item_price" wire:keydown.enter="addQuickItem" class="form-control form-control-sm text-center font-monospace" min="0">
                            @error('quick_item_price') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">الكمية المطلوبة</label>
                            <input type="number" wire:model="quick_item_qty" wire:keydown.enter="addQuickItem" class="form-control form-control-sm text-center font-monospace" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">الغاء</button>
                    <button type="button" wire:click="addQuickItem" class="btn btn-primary btn-sm fw-bold">ادراج بالسلة</button>
                </div>
            </div>
        </div>
    </div>

    {{-- نافذة تفاصيل الأرباح المتوقعة (F9) --}}
    <div class="modal fade" id="profitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="fw-bold mb-0">💰 تفاصيل الأرباح المتوقعة للفاتورة الحالية</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mb-3" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-bordered text-center align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-start">السلعة</th>
                                    <th>الكمية</th>
                                    <th>التكلفة (مفرق)</th>
                                    <th>البيع (مفرق)</th>
                                    <th>الربح الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($profit_details as $detail)
                                    <tr>
                                        <td class="text-start">{{ $detail['name'] }}</td>
                                        <td>{{ $detail['qty'] }}</td>
                                        <td class="font-monospace text-secondary">{{ number_format($detail['cost'], 2) }}</td>
                                        <td class="font-monospace">{{ number_format($detail['price'], 2) }}</td>
                                        <td class="font-monospace fw-bold {{ $detail['profit'] > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($detail['profit'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">لا توجد سلع في السلة.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="bg-light p-2 rounded">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold text-secondary">إجمالي رأس المال:</span>
                            <b class="font-monospace text-dark">{{ number_format($total_capital, 2) }} دج</b>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold text-success">صافي الأرباح المتوقعة:</span>
                            {{-- 1. تصبح باللون الأحمر إذا كانت الأرباح 0 أو أقل --}}
                            <b class="font-monospace {{ $total_profit > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($total_profit, 2) }} دج</b>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between p-1 bg-dark text-white rounded">
                            <span class="fw-bold">إجمالي قيمة الفاتورة:</span>
                            <b class="font-monospace">{{ number_format($final_total, 2) }} دج</b>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-success btn-sm w-100 fw-bold" data-bs-dismiss="modal">ممتاز، إتمام البيع</button>
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
                if(modalElement) {
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) { modalInstance.hide(); }
                }
            });

            Livewire.on('show-profit-modal', () => {
                const modalElement = document.getElementById('profitModal');
                if(modalElement) {
                    const modalInstance = new bootstrap.Modal(modalElement);
                    modalInstance.show();
                }
            });
            // إجبار المؤشر على الدخول لخانة "البيان / الخدمة" عند فتح نافذة الإضافة السريعة
    const quickModal = document.getElementById('quickItemModal');
    if (quickModal) {
        quickModal.addEventListener('shown.bs.modal', function () {
            const nameInput = document.getElementById('quick-item-name-input');
            if (nameInput) {
                nameInput.focus();
            }
        });
    }
        });

        // اختصارات لوحة المفاتيح للكاشير
        document.addEventListener('keydown', function(e) {
            const isTyping = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);
            const activeElement = e.target;

            // F4 - إنهاء الفاتورة
            if (e.key === 'F4') {
                e.preventDefault();
                Livewire.dispatch('shortcut-checkout');
            } 
            // F9 - إظهار تفاصيل الأرباح المتوقعة
            else if (e.key === 'F9') {
                e.preventDefault();
                Livewire.dispatch('showProfits');
            } 
                        // F12 - تعليق الفاتورة الحالية
            else if (e.key === 'F12') {
                e.preventDefault();
                const suspendBtn = document.getElementById('suspend-btn');
                if (suspendBtn) suspendBtn.click();
            }
            // F10 - إرسال المؤشر للباركود
            else if (e.key === 'F10') {
                e.preventDefault();
                const barcodeInput = document.getElementById('barcode-input');
                if (barcodeInput) {
                    barcodeInput.focus();
                    barcodeInput.select();
                }
            } 
            // F11 - فتح نافذة الإضافة السريعة وتوجيه المؤشر لاسم السلعة
            else if (e.key === 'F11') {
                e.preventDefault();
                const quickBtn = document.getElementById('quick-item-btn');
                if (quickBtn) quickBtn.click();
                // تأخير بسيط لانتظار فتح النافذة ثم توجيه المؤشر
                setTimeout(() => {
                    const nameInput = document.getElementById('quick-item-name-input');
                    if (nameInput) nameInput.focus();
                }, 300);
            } 
            // علامة النجمة (*) - التركيز على كمية آخر سلعة مضافة (وليست الأولى)
            else if (e.key === '*') {
                e.preventDefault();
                const qtyInputs = document.querySelectorAll('[id^="qty-input-"]');
                if (qtyInputs.length > 0) {
                    const lastQtyInput = qtyInputs[qtyInputs.length - 1]; // جلب آخر عنصر
                    lastQtyInput.focus();
                    lastQtyInput.select();
                }
            } 
            // Suppr (Delete) - حذف آخر عنصر مع رسالة تأكيد
            else if (e.key === 'Delete') {
                // إذا كان المؤشر في حقل فارغ أو ليس بحقل نصي، قم بتنفيذ الحذف
                if (!isTyping || activeElement.value === '') {
                    e.preventDefault();
                    if (confirm('⚠️ هل أنت متأكد من حذف آخر عنصر أضيف للسلة؟')) {
                        Livewire.dispatch('deleteLastItem');
                    }
                }
            }
        });
    </script>

    <script src="{{ asset('js/print-invoice.js') }}"></script>

    <style>
        .favorite-card {
            position: relative;
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 2px solid var(--fav-color, #872061);
            border-radius: 12px;
            padding: 15px 10px; /* تعديل الحواف الداخلية */
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            height: 100%;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            user-select: none;
        }
        .favorite-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            border-color: var(--fav-color, #872061);
        }
        .favorite-card:active {
            transform: scale(0.97);
        }
        .favorite-icon {
            font-size: 1.8rem;
            margin-bottom: 4px;
            line-height: 1;
        }
        .favorite-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: #333;
            line-height: 1.2;
            margin-bottom: 2px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .favorite-price {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--fav-color, #872061);
            font-family: monospace;
        }
        
        /* كلاس تأثير ظهور علامة + عند الضغط على المربع بالكامل */
        .click-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            color: var(--fav-color, #872061);
            opacity: 0;
            transition: opacity 0.1s;
            pointer-events: none; /* لضمان عدم إعاقة النقر */
        }
        .favorite-card:active .click-overlay {
            opacity: 1;
            transform: scale(1.2);
        }

        .color-preview {
            display: inline-block;
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1px solid #ddd;
            vertical-align: middle;
            margin-left: 4px;
        }
        .badge.bg-danger.text-white {
            background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
        }
    </style>
</div>