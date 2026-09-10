<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Supplier;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // متغيرات البحث والفلترة
    public $search = '';
    public $selected_category = '';

    // متغيرات نموذج السلعة
    public $product_id, $name, $barcode, $category_id, $unit_id, $supplier_id, $location;
    public $purchase_price = 0;
    public $price_1 = 0; 
    public $price_2 = 0; 
    public $price_3 = 0; 
    public $price_4 = 0; 
    public $current_stock = 0; 
    public $min_stock_alert = 5; 

    // نظام العلبة / الصندوق
    public $box_barcode;
    public $package_items_count = 1; 
    public $input_type = 'pieces'; 
    public $box_count = 0; 

    // متغيرات النوافذ المنبثقة السريعة
    public $new_category_name;
    public $new_unit_name;
    public $new_supplier_name;
    public $new_supplier_phone;

    public $is_edit = false;
    // 🟢 إضافة محرك الحسابات المالية الدقيقة للمستودع
   
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
            'box_barcode', 'package_items_count', 'input_type', 'box_count', 'is_edit'
        ]);
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

    public function saveProduct()
    {
        // التحقق من البيانات مع تخصيص رسائل الخطأ باللغة العربية
        $this->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode,' . $this->product_id,
            'purchase_price' => 'required|numeric|min:0',
            'price_1' => 'required|numeric|min:0',
            'package_items_count' => 'required|integer|min:1',
        ], [
            'name.required' => 'اسم السلعة حقل إجباري لا يمكن تركه فارغاً.',
            'barcode.required' => 'الباركود مطلوب، يرجى كتابته أو توليده تلقائياً.',
            'barcode.unique' => 'هذا الباركود مسجل مسبقاً لسلعة أخرى في النظام.',
            'purchase_price.required' => 'يرجى إدخال سعر الشراء.',
            'price_1.required' => 'يرجى إدخال سعر البيع (تجزئة).',
        ]);

        // احتساب الكمية الابتدائية الإجمالية بناءً على طريقة الإدخال اختيارياً
        $final_stock = (float) $this->current_stock;
        if ($this->input_type === 'boxes' && !$this->is_edit) {
            $final_stock = (float) $this->box_count * (int) $this->package_items_count;
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
                'current_stock' => $final_stock, 
                'min_stock_alert' => $this->min_stock_alert ?: 5, 
                'package_items_count' => $this->package_items_count ?: 1, 
                'box_barcode' => $this->box_barcode ?: null, 
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
        $this->box_barcode = $product->box_barcode;
        $this->package_items_count = $product->package_items_count;
        $this->input_type = 'pieces'; 
        $this->is_edit = true;
    }

    public function deleteProduct($id)
    {
        Product::destroy($id);
        session()->flash('success', 'تم حذف السلعة نهائياً.');
    }

    public function rendering()
    {
        if($this->search) { $this->resetPage(); }
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show p-2 small" role="alert">
            ✨ {{ session('success') }}
        </div>
    @endif

    <div class="row g-3">
        <!-- قسم المدخلات الأيمن -->
        <div class="col-md-5">
            <div class="card shadow-sm border-top-custom">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">{{ $is_edit ? '📝 تعديل السلعة المتطورة' : '➕ إضافة سلعة متطورة ومفصلة' }}</h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="saveProduct">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">اسم السلعة / الكتاب <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" class="form-control form-control-sm" required placeholder="مثال: كراس 96 صفحة السلام">
                            @error('name') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">الباركود الفردي (قطعة) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="text" wire:model="barcode" class="form-control text-start font-monospace" required placeholder="امسح بالليزر أو ولد تلقائياً">
                                <button type="button" wire:click="generateRandomBarcode" class="btn btn-warning fw-bold">⚡ توليد باركود</button>
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

                        <div class="card bg-light border-0 p-2 mb-2">
                            <span class="fw-bold small text-secondary mb-1">📦 هندسة الصناديق والتعبئة (الجملة)</span>
                            <div class="row g-2">
                                <div class="col-7">
                                    <label class="form-label text-muted small mb-0">باركود العلة بالكامل</label>
                                    <input type="text" wire:model="box_barcode" class="form-control form-control-sm font-monospace" placeholder="باركود العبوة الكبرى">
                                </div>
                                <div class="col-5">
                                    <label class="form-label text-muted small mb-0">السعة (كم قطعة؟)</label>
                                    <input type="number" wire:model="package_items_count" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
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

                        <div class="row g-2 mb-2 bg-gradient p-2 border rounded">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-danger">💵 سعر الشراء الأساسي (دج) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" wire:model="purchase_price" class="form-control form-control-sm fw-bold border-danger text-center" required>
                                @error('purchase_price') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                            </div>
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

                        @if(!$is_edit)
                        <div class="card border-warning p-2 mb-3 bg-white">
                            <label class="form-label small fw-bold mb-1">📉 جرد الكمية الابتدائية حسب:</label>
                            <div class="d-flex gap-3 mb-2 small">
                                <label><input type="radio" wire:model.live="input_type" value="pieces"> بالقطع الفردية</label>
                                <label><input type="radio" wire:model.live="input_type" value="boxes" class="text-warning"> بالعلب والكراتين</label>
                            </div>
                            @if($input_type === 'pieces')
                                <input type="number" wire:model="current_stock" class="form-control form-control-sm" placeholder="أدخل عدد القطع الإجمالي">
                            @else
                                <div class="input-group input-group-sm">
                                    <input type="number" wire:model="box_count" class="form-control" placeholder="عدد العلب">
                                    <span class="input-group-text bg-warning-subtle text-dark small">علبة × {{ $package_items_count }} قطعة</span>
                                </div>
                            @endif
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label small fw-bold">حد الطلب للتنبيه بالنواقص</label>
                            <input type="number" wire:model="min_stock_alert" class="form-control form-control-sm">
                        </div>

                        <!-- عرض ملخص لكافة أخطاء الإدخال إن وجدت لمنع الحفظ الأعمى -->
                        @if ($errors->any())
                            <div class="alert alert-danger p-2 small mb-2">
                                <ul class="mb-0 px-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-bold">
                                💾 {{ $is_edit ? 'تحديث السلعة المتكاملة' : 'حفظ السلعة بالمخزن والأرفف' }}
                            </button>
                            @if($is_edit)
                                <button type="button" wire:click="resetFields" class="btn btn-secondary btn-sm">إلغاء</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- جدول المراقبة الأيسر -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex gap-2">
                        <input type="text" wire:model.live="search" class="form-control form-control-sm w-70" placeholder="🔍 ابحث باسم، باركود قطعة، باركود علبة، أو الرف...">
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
                                              ->orWhere('box_barcode', 'like', '%'.$this->search.'%')
                                              ->orWhere('location', 'like', '%'.$this->search.'%');
                                    })
                                    ->when($this->selected_category, function($query) {
                                        $query->where('category_id', $this->selected_category);
                                    })
                                    ->paginate(10);
                            @endphp

                            @forelse($productsList as $product)
                                <tr>
                                    <td class="text-start font-monospace p-2">
                                        <div>🏷️ {{ $product->barcode }}</div>
                                        @if($product->box_barcode)
                                            <div class="text-muted text-xs">📦 {{ $product->box_barcode }} (×{{ $product->package_items_count }})</div>
                                        @endif
                                    </td>
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
                                            <button onclick="printBarcodeTicket('{{ $product->name }}', '{{ $product->price_1 }}', '{{ $product->barcode }}')" class="btn btn-sm btn-warning px-1 py-0" title="طباعة التيكيت الصغير">🖨️ تيكيت</button>
                                            <button wire:click="deleteProduct({{ $product->id }})" onclick="confirm('حذف نهائي؟') || event.stopImmediatePropagation()" class="btn btn-sm btn-outline-danger px-1 py-0">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted p-4">لا توجد منتجات مطابقة حالياً.</td>
                                </tr>
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

    <!-- النوافذ المنبثقة (Modals) -->
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

    <!-- محرك السكريبت -->
    <script>
    function printBarcodeTicket(name, price, barcode) {
        const printWindow = window.open('', '_blank', 'width=300,height=200');
        printWindow.document.write(`
            <html>
            <head>
                <title>طباعة ملصق السلعة</title>
                <style>
                    @page { size: 50mm 30mm; margin: 0; }
                    body { font-family: 'Arial', sans-serif; direction: rtl; text-align: center; margin: 2px; padding: 0; font-size: 11px; }
                    .store-name { font-size: 9px; font-weight: bold; border-bottom: 1px dashed #000; padding-bottom: 2px; margin-bottom: 3px; }
                    .product-name { font-weight: bold; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
                    .price { font-size: 14px; font-weight: bold; color: #000; margin-bottom: 1px; }
                    .barcode-num { font-family: 'Courier New', monospace; font-size: 12px; font-weight: bold; letter-spacing: 2px; }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="store-name">✨ مكتبة السلام ✨</div>
                <div class="product-name">\${name}</div>
                <div class="price">\${parseFloat(price).toFixed(2)} دج</div>
                <div class="barcode-num">| |||| | || \${barcode} || |</div>
            </body>
            </html>
        `);
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
    </style>
</div>