<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Favorite;
use App\Models\SuspendedCart;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class PosComponent extends Component
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

    // خصائص نظام المفضلات
    public $favorites = [];
    public $showFavoritesManager = false;

    // نموذج اضافة/تعديل مفضلة
    public $fav_id = null;
    public $fav_name = '';
    public $fav_price = 0;
    public $fav_icon = '📦';
    public $fav_color = '#872061';
    public $fav_sort_order = 0;

    // خصائص الفواتير المعلقة
    public $suspendedCarts = [];

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
    // دوال الفواتير المعلقة (Park Sale)
    // ═══════════════════════════════════════════════

    public function loadSuspendedCarts()
    {
        $this->suspendedCarts = SuspendedCart::where('user_id', auth()->id() ?? 1)
            ->with('customer')
            ->latest()
            ->get();
    }

    public function suspendCurrentCart()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'السلة فارغة، لا يمكن تعليقها!');
            return;
        }

        SuspendedCart::create([
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
        session()->flash('success', '🅿️ تم تعليق الفاتورة بنجاح! يمكنك خدمة زبون آخر واسترجاعها لاحقاً.');
    }

    public function restoreSuspendedCart($id)
    {
        $suspended = SuspendedCart::find($id);
        if (!$suspended) return;

        $this->cart = json_decode($suspended->cart_data, true);
        $this->selected_customer_id = $suspended->customer_id;
        $this->discount_amount = $suspended->discount_amount;
        $this->payment_method = $suspended->payment_method;
        $this->paid_amount = $suspended->paid_amount;

        $this->calculateTotals();

        $suspended->delete();
        $this->loadSuspendedCarts();

        session()->flash('success', '🔄 تم استرجاع الفاتورة المعلقة! أكمل البيع الآن.');
    }

    public function deleteSuspendedCart($id)
    {
        SuspendedCart::find($id)?->delete();
        $this->loadSuspendedCarts();
        session()->flash('success', '🗑️ تم إلغاء الفاتورة المعلقة.');
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
            if ($item['product_id'] && isset($products[$item['product_id']])) {
                $product = $products[$item['product_id']];
                $cost = $product->cost_price ?? $product->purchase_price ?? 0; 
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
}