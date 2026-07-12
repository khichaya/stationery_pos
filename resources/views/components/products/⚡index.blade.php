<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Supplier;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // بحث وفلترة
    public $search = '';
    public $selected_category = '';

    // بيانات السلعة
    public $product_id, $name, $barcode, $category_id, $unit_id, $supplier_id, $location;
    public $purchase_price = 0;
    public $price_1 = 0;
    public $price_2 = 0;
    public $price_3 = 0;
    public $price_4 = 0;
    public $current_stock = 0;
    public $min_stock_alert = 5;

    // صورة السلعة
    public $photo;
    public $existing_image;

    // النوافذ السريعة
    public $new_category_name;
    public $new_unit_name;
    public $new_supplier_name;
    public $new_supplier_phone;

    public $is_edit = false;
    public $activeTab = 'basic';

    public function addSupplierQuickly()
    {
        $this->validate([
            'new_supplier_name' => 'required|string|max:255|unique:suppliers,name',
            'new_supplier_phone' => 'nullable|string|max:20'
        ]);

        Supplier::create([
            'name' => $this->new_supplier_name,
            'phone' => $this->new_supplier_phone
        ]);

        $this->new_supplier_name = '';
        $this->new_supplier_phone = '';

        $this->dispatch('close-modal', modalId: 'quickSupplierModal');
        session()->flash('success', 'تم إضافة المورد الجديد بنجاح!');
    }

    public function resetFields()
    {
        $this->reset([
            'product_id', 'name', 'barcode', 'category_id', 'unit_id', 'supplier_id', 'location',
            'purchase_price', 'price_1', 'price_2', 'price_3', 'price_4', 'current_stock', 'min_stock_alert',
            'photo', 'existing_image', 'is_edit'
        ]);
        $this->activeTab = 'basic';
    }

    public function generateRandomBarcode()
    {
        do {
            $code = '210' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $code)->exists());

        $this->barcode = $code;
    }

    public function addCategoryQuickly()
    {
        $this->validate(['new_category_name' => 'required|string|max:255|unique:categories,name']);
        Category::create(['name' => $this->new_category_name]);
        $this->new_category_name = '';
        $this->dispatch('close-modal', modalId: 'quickCategoryModal');
        session()->flash('success', 'تم إضافة الصنف الجديد بنجاح!');
    }

    public function addUnitQuickly()
    {
        $this->validate(['new_unit_name' => 'required|string|max:255|unique:units,name']);
        Unit::create(['name' => $this->new_unit_name]);
        $this->new_unit_name = '';
        $this->dispatch('close-modal', modalId: 'quickUnitModal');
        session()->flash('success', 'تم إضافة الوحدة الجديدة بنجاح!');
    }

    public function removePhoto()
    {
        $this->photo = null;
        $this->existing_image = null;
    }

    public function saveProduct()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode,' . $this->product_id,
            'purchase_price' => 'required|numeric|min:0',
            'price_1' => 'required|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
        ], [
            'name.required' => 'اسم السلعة حقل إجباري لا يمكن تركه فارغاً.',
            'barcode.required' => 'الباركود مطلوب، يرجى كتابته أو توليده تلقائياً.',
            'barcode.unique' => 'هذا الباركود مسجل مسبقاً لسلعة أخرى في النظام.',
            'purchase_price.required' => 'يرجى إدخال سعر الشراء.',
            'price_1.required' => 'يرجى إدخال سعر البيع (تجزئة).',
            'photo.image' => 'يجب أن يكون الملف صورة صالحة.',
            'photo.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ]);

        $imagePath = $this->existing_image;
        if ($this->photo) {
            $imagePath = $this->photo->store('products', 'public');
        }

        Product::updateOrCreate(
            ['id' => $this->product_id],
            [
                'name' => $this->name,
                'barcode' => $this->barcode,
                'category_id' => $this->category_id ?: null,
                'unit_id' => $this->unit_id ?: null,
                'supplier_id' => $this->supplier_id ?: null,
                'location' => $this->location ?: null,
                'purchase_price' => $this->purchase_price ?: 0,
                'price_1' => $this->price_1 ?: 0,
                'price_2' => $this->price_2 ?: ($this->price_1 ?: 0),
                'price_3' => $this->price_3 ?: ($this->price_1 ?: 0),
                'price_4' => $this->price_4 ?: ($this->price_1 ?: 0),
                'current_stock' => $this->current_stock ?: 0,
                'min_stock_alert' => $this->min_stock_alert ?: 5,
                'image' => $imagePath,
            ]
        );

        session()->flash('success', $this->is_edit ? 'تم تحديث السلعة وبياناتها بنجاح!' : 'تم إضافة السلعة وحساب مخزونها بنجاح!');
        $this->resetFields();
    }

    public function editProduct($id)
    {
        $product = Product::findOrFail($id);
        $this->product_id = $product->id;
        $this->name = $product->name;
        $this->barcode = $product->barcode;
        $this->category_id = $product->category_id;
        $this->unit_id = $product->unit_id;
        $this->supplier_id = $product->supplier_id;
        $this->location = $product->location;
        $this->purchase_price = $product->purchase_price;
        $this->price_1 = $product->price_1;
        $this->price_2 = $product->price_2;
        $this->price_3 = $product->price_3;
        $this->price_4 = $product->price_4;
        $this->current_stock = $product->current_stock;
        $this->min_stock_alert = $product->min_stock_alert;
        $this->existing_image = $product->image ?? null;
        $this->photo = null;
        $this->is_edit = true;
        $this->activeTab = 'basic';
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        // حذف العناصر المرتبطة أولاً ثم حذف السلعة
        \App\Models\SaleItem::where('product_id', $id)->delete();
        \App\Models\PurchaseItem::where('product_id', $id)->delete();

        $product->delete();

        session()->flash('success', 'تم حذف السلعة نهائياً.');
    }

    public function rendering()
    {
        if ($this->search) { $this->resetPage(); }
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show p-2 small" role="alert">
            ✨ {{ session('success') }}
        </div>
    @endif

    {{-- ========== قسم الإحصائيات ========== --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card stat-card-purple">
                <div class="card-body d-flex align-items-center text-white p-3">
                    <div class="stat-icon-box me-3">📦</div>
                    <div>
                        <div class="stat-label">إجمالي السلع</div>
                        <div class="stat-value">{{ \App\Models\Product::count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card stat-card-green">
                <div class="card-body d-flex align-items-center text-white p-3">
                    <div class="stat-icon-box me-3">💰</div>
                    <div>
                        <div class="stat-label">قيمة المخزون</div>
                        <div class="stat-value">{{ number_format(\App\Models\Product::sum(\DB::raw('purchase_price * current_stock')), 0) }} دج</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card stat-card-pink">
                <div class="card-body d-flex align-items-center text-white p-3">
                    <div class="stat-icon-box me-3">⚠️</div>
                    <div>
                        <div class="stat-label">نواقص المخزون</div>
                        <div class="stat-value">{{ \App\Models\Product::whereColumn('current_stock', '<=', 'min_stock_alert')->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card stat-card-blue">
                <div class="card-body d-flex align-items-center text-white p-3">
                    <div class="stat-icon-box me-3">📈</div>
                    <div>
                        <div class="stat-label">متوسط هامش الربح</div>
                        <div class="stat-value">
                            @php
                                $avgMargin = \App\Models\Product::where('purchase_price', '>', 0)
                                    ->avg(\DB::raw('((price_1 - purchase_price) / purchase_price) * 100'));
                            @endphp
                            {{ number_format($avgMargin ?? 0, 1) }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">📊 توزيع السلع حسب الأصناف</h6>
                </div>
                <div class="card-body">
                    @php
                        $categoryStats = \App\Models\Category::withCount('products')
                            ->having('products_count', '>', 0)
                            ->orderByDesc('products_count')
                            ->take(6)
                            ->get();
                        $maxCount = $categoryStats->max('products_count') ?: 1;
                    @endphp
                    @forelse($categoryStats as $cat)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold">{{ $cat->name }}</span>
                                <span class="small text-muted">{{ $cat->products_count }} سلعة</span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ ($cat->products_count / $maxCount) * 100 }}%; background: linear-gradient(90deg, #872061, #c44d8a); border-radius: 4px;"
                                     aria-valuenow="{{ $cat->products_count }}" aria-valuemin="0" aria-valuemax="{{ $maxCount }}">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">لا توجد أصناف مسجلة بعد</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">🚨 السلع منخفضة المخزون</h6>
                </div>
                <div class="card-body p-0">
                    @php
                        $lowStockProducts = \App\Models\Product::whereColumn('current_stock', '<=', 'min_stock_alert')
                            ->orderBy('current_stock')
                            ->take(8)
                            ->get();
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3">السلعة</th>
                                    <th>الباركود</th>
                                    <th class="text-center">المخزون</th>
                                    <th class="text-center">الحد الأدنى</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockProducts as $product)
                                    <tr>
                                        <td class="px-3 fw-semibold">{{ $product->name }}</td>
                                        <td class="font-monospace text-muted">{{ $product->barcode }}</td>
                                        <td class="text-center"><span class="badge bg-danger">{{ $product->current_stock }}</span></td>
                                        <td class="text-center text-muted">{{ $product->min_stock_alert }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">🎉 جميع السلع بمخزون آمن</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <div class="card shadow-sm border-top-custom">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">{{ $is_edit ? '📝 تعديل السلعة' : '➕ إضافة سلعة جديدة' }}</h6>
                </div>
                <div class="card-body p-0">
                    <form wire:submit.prevent="saveProduct">
                        <ul class="nav nav-tabs product-tabs px-3 pt-2" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link {{ $activeTab === 'basic' ? 'active' : '' }}"
                                        wire:click.prevent="$set('activeTab', 'basic')">🧾 أساسي</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link {{ $activeTab === 'pricing' ? 'active' : '' }}"
                                        wire:click.prevent="$set('activeTab', 'pricing')">💰 الأسعار</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link {{ $activeTab === 'stock' ? 'active' : '' }}"
                                        wire:click.prevent="$set('activeTab', 'stock')">📦 المخزون</button>
                            </li>
                        </ul>
                        <div class="p-3">
                            <div class="{{ $activeTab === 'basic' ? '' : 'd-none' }}">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">صورة السلعة</label>
                                    <div class="photo-upload-box">
                                        @if ($photo)
                                            <img src="{{ $photo->temporaryUrl() }}" class="photo-preview">
                                        @elseif ($existing_image)
                                            <img src="{{ Storage::url($existing_image) }}" class="photo-preview">
                                        @else
                                            <div class="photo-placeholder">
                                                <i class="bi bi-image fs-2 text-muted"></i>
                                                <span class="text-muted small d-block mt-1">اضغط لاختيار صورة</span>
                                            </div>
                                        @endif
                                        <input type="file" wire:model="photo" class="photo-input" accept="image/*">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span wire:loading wire:target="photo" class="text-primary text-xs">⏳ جاري رفع الصورة...</span>
                                        @if($photo || $existing_image)
                                            <button type="button" wire:click="removePhoto" class="btn btn-link btn-sm text-danger p-0 text-xs">إزالة الصورة</button>
                                        @endif
                                    </div>
                                    @error('photo') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">اسم السلعة / الكتاب <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="name" class="form-control form-control-sm" required placeholder="مثال: كراس 96 صفحة السلام">
                                    @error('name') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">الباركود <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" wire:model="barcode" class="form-control text-start font-monospace" required placeholder="امسح بالليزر أو ولد تلقائياً">
                                        <button type="button" wire:click="generateRandomBarcode" class="btn btn-warning fw-bold">⚡ توليد</button>
                                    </div>
                                    @error('barcode') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-bold mb-0">الصنف</label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#quickCategoryModal">➕ جديد</button>
                                        </div>
                                        <select wire:model="category_id" class="form-select form-select-sm">
                                            <option value="">-- عام --</option>
                                            @foreach(\App\Models\Category::all() as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-bold mb-0">الوحدة</label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#quickUnitModal">➕ جديد</button>
                                        </div>
                                        <select wire:model="unit_id" class="form-select form-select-sm">
                                            <option value="">-- قطعة --</option>
                                            @foreach(\App\Models\Unit::all() as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-bold mb-0">الممول / المورد</label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#quickSupplierModal">➕ جديد</button>
                                        </div>
                                        <select wire:model="supplier_id" class="form-select form-select-sm">
                                            <option value="">-- غير محدد --</option>
                                            @foreach(\App\Models\Supplier::all() as $sup)
                                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold mb-1">مكان التخزين (الرف)</label>
                                        <input type="text" wire:model="location" class="form-control form-control-sm" placeholder="مثال: الرف A4">
                                    </div>
                                </div>
                            </div>
                            <div class="{{ $activeTab === 'pricing' ? '' : 'd-none' }}">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-danger">💵 سعر الشراء الأساسي (دج) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model="purchase_price" class="form-control form-control-sm fw-bold border-danger text-center" required>
                                    @error('purchase_price') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small text-success fw-bold">💰 سعر 1 (تجزئة) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" wire:model="price_1" class="form-control form-control-sm text-center fw-bold border-success" required>
                                        @error('price_1') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-primary">💰 سعر 2 (شبه جملة)</label>
                                        <input type="number" step="0.01" wire:model="price_2" class="form-control form-control-sm text-center">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-info">💰 سعر 3 (جملة)</label>
                                        <input type="number" step="0.01" wire:model="price_3" class="form-control form-control-sm text-center">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-dark">💰 سعر 4 (خاص)</label>
                                        <input type="number" step="0.01" wire:model="price_4" class="form-control form-control-sm text-center">
                                    </div>
                                </div>
                            </div>
                            <div class="{{ $activeTab === 'stock' ? '' : 'd-none' }}">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">الكمية الحالية بالمخزون</label>
                                    <input type="number" wire:model="current_stock" class="form-control form-control-sm" placeholder="أدخل عدد القطع الإجمالي">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">حد الطلب للتنبيه بالنواقص</label>
                                    <input type="number" wire:model="min_stock_alert" class="form-control form-control-sm">
                                </div>
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger p-2 small mb-2 mt-2">
                                    <ul class="mb-0 px-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-bold">
                                    💾 {{ $is_edit ? 'تحديث السلعة' : 'حفظ السلعة' }}
                                </button>
                                @if($is_edit)
                                    <button type="button" wire:click="resetFields" class="btn btn-secondary btn-sm">إلغاء</button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex gap-2">
                        <input type="text" wire:model.live="search" class="form-control form-control-sm w-70" placeholder="🔍 ابحث باسم، باركود، أو الرف...">
                        <select wire:model.live="selected_category" class="form-select form-select-sm w-30">
                            <option value="">كل الأصناف</option>
                            @foreach(\App\Models\Category::all() as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>الصورة</th>
                                <th>الباركود</th>
                                <th>اسم السلعة / الممول</th>
                                <th>الشراء</th>
                                <th>الأسعار (1-4)</th>
                                <th>المخزون</th>
                                <th>الخيارات والطباعة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $productsList = Product::when($this->search, function($query) {
                                        $query->where('name', 'like', '%'.$this->search.'%')
                                              ->orWhere('barcode', 'like', '%'.$this->search.'%')
                                              ->orWhere('location', 'like', '%'.$this->search.'%');
                                    })
                                    ->when($this->selected_category, function($query) {
                                        $query->where('category_id', $this->selected_category);
                                    })
                                    ->paginate(10);
                            @endphp
                            @forelse($productsList as $product)
                                <tr>
                                    <td>
                                        @if($product->image ?? false)
                                            <img src="{{ Storage::url($product->image) }}" class="row-thumb" onclick="showImagePreview('{{ Storage::url($product->image) }}', '{{ $product->name }}')" onerror="this.src=''; this.classList.add('row-thumb-broken');">
                                        @else
                                            <div class="row-thumb row-thumb-placeholder"><i class="bi bi-image"></i></div>
                                        @endif
                                    </td>
                                    <td class="text-start font-monospace p-2">🏷️ {{ $product->barcode }}</td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $product->name }}</div>
                                        <div class="text-xs text-muted">📍 {{ $product->location ?: 'غير محدد' }}</div>
                                    </td>
                                    <td class="small">{{ number_format($product->purchase_price, 2) }}</td>
                                    <td class="text-start px-2 text-xs">
                                        <div class="text-success">تجزئة: <b>{{ number_format($product->price_1, 2) }}</b></div>
                                        <div class="text-primary">شبه: {{ number_format($product->price_2, 2) }}</div>
                                        <div class="text-info">جملة: {{ number_format($product->price_3, 2) }}</div>
                                        <div class="text-dark">خاص: {{ number_format($product->price_4, 2) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $product->current_stock <= $product->min_stock_alert ? 'bg-danger' : 'bg-dark' }}">
                                            {{ $product->current_stock }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button wire:click="editProduct({{ $product->id }})" class="btn btn-sm btn-outline-primary px-1 py-0">✏️</button>
                                            <button onclick="printBarcodeTicket('{{ addslashes($product->name) }}', '{{ $product->price_1 }}', '{{ $product->barcode }}')" class="btn btn-sm btn-warning px-1 py-0" title="طباعة التيكيت الصغير">🖨️ تيكيت</button>
                                            <button wire:click="deleteProduct({{ $product->id }})" onclick="return confirm('هل أنت متأكد من حذف هذه السلعة نهائياً؟ لا يمكن التراجع!')" class="btn btn-sm btn-outline-danger px-1 py-0">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted p-4">لا توجد منتجات مطابقة حالياً.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white pt-2">
                    {{ $productsList->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- ====== نافذة معاينة صورة السلعة ====== --}}
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="fw-bold mb-0" id="previewImageTitle">معاينة الصورة</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="previewImageSrc" src="" class="img-fluid" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="quickCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="fw-bold mb-0">📁 إضافة صنف جديد للمكتبة</h6>
                </div>
                <div class="modal-body">
                    <input type="text" wire:model="new_category_name" class="form-control form-control-sm" placeholder="مثال: أدوات مدرسية">
                    @error('new_category_name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="modal-footer p-1">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" wire:click="addCategoryQuickly" class="btn btn-primary btn-sm">حفظ الصنف</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="quickUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="fw-bold mb-0">📐 إضافة وحدة قياس جديدة</h6>
                </div>
                <div class="modal-body">
                    <input type="text" wire:model="new_unit_name" class="form-control form-control-sm" placeholder="مثال: علبة">
                    @error('new_unit_name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="modal-footer p-1">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" wire:click="addUnitQuickly" class="btn btn-primary btn-sm">حفظ الوحدة</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="quickSupplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="fw-bold mb-0">🚚 إضافة مورد / ممول جديد</h6>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">اسم المورد / الشركة</label>
                        <input type="text" wire:model="new_supplier_name" class="form-control form-control-sm" placeholder="مثال: شركة السلام للتوزيع">
                        @error('new_supplier_name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-1">
                        <label class="form-label small">رقم الهاتف</label>
                        <input type="text" wire:model="new_supplier_phone" class="form-control form-control-sm text-start" placeholder="06xxxxxxxx">
                    </div>
                </div>
                <div class="modal-footer p-1">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" wire:click="addSupplierQuickly" class="btn btn-primary btn-sm">حفظ المورد</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== مكتبة JsBarcode  JsBarcode.all.min.js  للباركود الحقيقي ====== --}}
    <script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>

    <script>
    // ====== معاينة الصورة ======
    function showImagePreview(src, title) {
        document.getElementById('previewImageSrc').src = src;
        document.getElementById('previewImageTitle').textContent = title;
        const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        modal.show();
    }

function printBarcodeTicket(name, price, barcode) {
    const printWindow = window.open('', '_blank', 'width=400,height=300');
    
    // إنشاء محتوى HTML كامل
    const htmlContent = `
        <!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta charset="UTF-8">
            <title>طباعة ملصق - ${name}</title>
            <style>
                @page { size: 58mm 40mm; margin: 0; }
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
                    direction: rtl;
                    text-align: center;
                    width: 58mm;
                    padding: 2mm;
                    font-size: 10px;
                    background: #fff;
                }
                .store-name {
                    font-size: 9px;
                    font-weight: bold;
                    border-bottom: 1px dashed #000;
                    padding-bottom: 2px;
                    margin-bottom: 3px;
                    color: #872061;
                }
                .product-name {
                    font-weight: bold;
                    font-size: 11px;
                    margin-bottom: 2px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-width: 54mm;
                }
                .price {
                    font-size: 16px;
                    font-weight: bold;
                    color: #000;
                    margin: 2px 0;
                }
                .price-currency { font-size: 10px; }
                .barcode-container {
                    margin-top: 3px;
                    display: flex;
                    justify-content: center;
                }
                .barcode-container svg {
                    max-width: 54mm;
                    height: 30px;
                }
                .barcode-text {
                    font-family: 'Courier New', monospace;
                    font-size: 9px;
                    letter-spacing: 1px;
                    margin-top: 1px;
                }
                .date-line {
                    font-size: 7px;
                    color: #666;
                    margin-top: 2px;
                }
            </style>
        </head>
        <body>
            <div class="store-name">✨ مكتبة السلام ✨</div>
            <div class="product-name">${name}</div>
            <div class="price">
                ${parseFloat(price).toLocaleString('fr-DZ', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                <span class="price-currency">دج</span>
            </div>
            <div class="barcode-container">
                <svg id="barcode-svg"></svg>
            </div>
            <div class="barcode-text">${barcode}</div>
            <div class="date-line">${new Date().toLocaleDateString('ar-DZ')}</div>
            
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.12.3/dist/JsBarcode.all.min.js"><\/script>
            <script>
                window.onload = function() {
                    try {
                        JsBarcode("#barcode-svg", "${barcode}", {
                            format: "CODE128",
                            width: 1.5,
                            height: 30,
                            displayValue: false,
                            margin: 0,
                            background: "#ffffff",
                            lineColor: "#000000"
                        });
                        setTimeout(function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 500);
                        }, 300);
                    } catch(e) {
                        console.error("Barcode error:", e);
                        document.querySelector('.barcode-container').innerHTML = 
                            '<div style="font-size:20px;letter-spacing:3px;">| ' + "${barcode}" + ' |</div>';
                        window.print();
                        window.close();
                    }
                };
            <\/script>
        </body>
        </html>
    `;
    
    printWindow.document.open();
    printWindow.document.write(htmlContent);
    printWindow.document.close();
}

    document.addEventListener('livewire:init', () => {
        Livewire.on('close-modal', (event) => {
            const modalElement = document.getElementById(event.modalId);
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) { modalInstance.hide(); }
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('close-modal', event => {
            const modalElement = document.getElementById(event.detail.modalId);
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                modalInstance.hide();
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) { backdrop.remove(); }
            }
        });
    });
    </script>

    <style>
        .border-top-custom { border-top: 4px solid #872061; }
        .text-xs { font-size: 0.75rem; }

        .product-tabs { border-bottom: 1px solid #eee; }
        .product-tabs .nav-link {
            border: none;
            background: none;
            color: #8a97a3;
            font-weight: 700;
            font-size: .85rem;
            padding: .55rem .9rem;
            border-radius: 8px 8px 0 0;
        }
        .product-tabs .nav-link.active {
            color: #872061;
            background: #f8f0f5;
            border-bottom: 3px solid #872061;
        }

        .photo-upload-box {
            position: relative;
            width: 100%;
            height: 140px;
            border: 2px dashed #d8dee3;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafbfc;
            cursor: pointer;
            transition: border-color .2s ease;
        }
        .photo-upload-box:hover { border-color: #0d7cb5; }
        .photo-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .photo-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-placeholder { text-align: center; }

        .row-thumb {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }
        .row-thumb-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f3f5;
            color: #adb5bd;
            font-size: 1.1rem;
        }

        .stat-card {
            border-radius: 16px;
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
        }
        .stat-card-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card-green  { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card-pink   { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card-blue   { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

        .stat-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .stat-label {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.8);
        }
        .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
        }
        .rounded-4 { border-radius: 16px !important; }
    </style>
</div>