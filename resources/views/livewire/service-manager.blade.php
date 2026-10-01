<?php

use Livewire\Component;
use App\Models\Service;
use App\Models\Customer;
use App\Models\FavoriteService;
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
    
    public $service_id, $service_type, $price = 0, $quantity = 1, $selected_customer_id, $payment_method = 'full', $paid_amount = 0;
    public $is_edit = false;
    
    public $favorites_services = [];
    public $showFavoritesManager = false;
    public $fav_service_type = '';
    public $fav_service_price = 0;
    public $fav_service_quantity = 1;

    // ✅ تعيين تاريخ اليوم تلقائياً عند فتح الصفحة
    public function mount()
    {
        $this->from_date = now()->format('Y-m-d');
        $this->to_date = now()->format('Y-m-d');
        
        $this->loadFavoriteServices();
    }

    // ✅ دوال إعادة الترقيم عند تغيير الفلاتر
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFromDate() { $this->resetPage(); }
    public function updatingToDate() { $this->resetPage(); }

    public function clearDates()
    {
        $this->from_date = null;
        $this->to_date = null;
    }

    public function resetFields()
    {
        $this->reset(['service_id', 'service_type', 'price', 'quantity', 'selected_customer_id', 'payment_method', 'paid_amount', 'is_edit']);
        $this->payment_method = 'full';
        $this->quantity = 1;
        $this->price = 0;
        $this->paid_amount = 0;
    }

    // ═══════════════════════════════════════════════
    // دوال المفضلة
    // ═══════════════════════════════════════════════

    public function loadFavoriteServices()
    {
        $this->favorites_services = FavoriteService::where('user_id', auth()->id() ?? 1)->get();
    }

    public function toggleFavoritesManager()
    {
        $this->showFavoritesManager = !$this->showFavoritesManager;
    }

    public function addFavoriteService()
    {
        $this->validate([
            'fav_service_type' => 'required|string|max:255',
            'fav_service_price' => 'required|numeric|min:0',
            'fav_service_quantity' => 'required|integer|min:1',
        ], [
            'fav_service_type.required' => 'نوع الخدمة مطلوب.',
            'fav_service_price.required' => 'السعر مطلوب.',
        ]);

        FavoriteService::create([
            'user_id' => auth()->id() ?? 1,
            'service_type' => $this->fav_service_type,
            'price' => $this->fav_service_price,
            'quantity' => $this->fav_service_quantity,
        ]);

        $this->reset(['fav_service_type', 'fav_service_price', 'fav_service_quantity']);
        $this->fav_service_quantity = 1;
        $this->loadFavoriteServices();
        session()->flash('success', '⭐ تمت إضافة القالب للمفضلة بنجاح!');
    }

    public function deleteFavoriteService($favId)
    {
        $fav = FavoriteService::find($favId);
        if ($fav) {
            $fav->delete();
            $this->loadFavoriteServices();
            session()->flash('success', '🗑️ تمت إزالة القالب من المفضلة.');
        }
    }

    public function useFavoriteService($favId)
    {
        $fav = FavoriteService::find($favId);
        
        if ($fav) {
            $this->resetFields();
            $this->service_type = $fav->service_type;
            $this->price = (float) $fav->price;
            $this->quantity = (int) $fav->quantity;
            $this->calculatePaidAmount();
            session()->flash('success', '✨ تم تحميل القالب! اضغط "تأكيد وترحيل المعاملة" مباشرة.');
        }
    }

    // ═══════════════════════════════════════════════
    // الدوال الأساسية للخدمات
    // ═══════════════════════════════════════════════

    public function calculatePaidAmount()
    {
        $total = (float) $this->price * (int) $this->quantity;
        if ($this->payment_method === 'full') {
            $this->paid_amount = $total;
        } elseif ($this->payment_method === 'debt') {
            $this->paid_amount = 0;
        }
    }

    public function updatedPrice() { $this->calculatePaidAmount(); }
    public function updatedQuantity() { $this->calculatePaidAmount(); }
    public function updatedPaymentMethod() { $this->calculatePaidAmount(); }

    public function saveService()
    {
        $total_amount = $this->price * $this->quantity;

        $this->validate([
            'service_type' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:full,partial,debt',
            'paid_amount' => 'required|numeric|min:0|max:' . $total_amount,
        ]);

        if (in_array($this->payment_method, ['partial', 'debt']) && !$this->selected_customer_id) {
            session()->flash('error', '⚠️ يجب اختيار حساب الزبون لتقييد الدين باسمه.');
            return;
        }

        DB::beginTransaction();
        try {
            $debt = $total_amount - $this->paid_amount;

            if ($this->is_edit && $this->service_id) {
                $oldService = Service::find($this->service_id);
                if ($oldService && $oldService->customer_id) {
                    $oldDebt = ($oldService->price * $oldService->quantity) - $oldService->paid_amount;
                    if ($oldDebt > 0) {
                        Customer::where('name', optional($oldService->customer)->name)
                                ->where('observation', 'like', '%خدمة رقم #' . $oldService->id . '%')
                                ->delete();
                    }
                }
            }

            $service = Service::updateOrCreate(
                ['id' => $this->service_id],
                [
                    'service_type' => $this->service_type,
                    'price' => $this->price,
                    'quantity' => $this->quantity,
                    'user_id' => auth()->id() ?? 1,
                    'customer_id' => $this->selected_customer_id ?: null,
                    'payment_method' => $this->payment_method,
                    'paid_amount' => $this->paid_amount,
                ]
            );

            if ($debt > 0 && $this->selected_customer_id) {
                $customer = Customer::find($this->selected_customer_id);
                if ($customer) {
                    Customer::create([
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'total_debt' => $debt,
                        'observation' => 'دين ' . $this->service_type . ' (خدمة رقم #' . $service->id . ')',
                    ]);
                }
            }

            DB::commit();
            session()->flash('success', $this->is_edit ? 'تم تحديث الخدمة بنجاح!' : 'تم تسجيل الخدمة المبتكرة بنجاح!');
            $this->resetFields();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function editService($id)
    {
        $service = Service::findOrFail($id);
        $this->service_id = $service->id;
        $this->service_type = $service->service_type;
        $this->price = $service->price;
        $this->quantity = $service->quantity;
        $this->selected_customer_id = $service->customer_id;
        $this->payment_method = $service->payment_method;
        $this->paid_amount = $service->paid_amount;
        $this->is_edit = true;
    }

    public function deleteService($id)
    {
        DB::beginTransaction();
        try {
            $service = Service::findOrFail($id);
            if (in_array($service->payment_method, ['partial', 'debt']) && $service->customer_id) {
                Customer::where('name', optional($service->customer)->name)
                        ->where('observation', 'like', '%خدمة رقم #' . $service->id . '%')
                        ->delete();
            }
            $service->delete();
            DB::commit();
            session()->flash('success', 'تم حذف سجل الخدمة بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'حدث خطأ أثناء الحذف.');
        }
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success border-0 shadow-sm p-2 small fw-bold mb-3">✨ {{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger border-0 shadow-sm p-2 small fw-bold mb-3">⚠️ {{ session('error') }}</div>
    @endif

    {{-- ✅ قسم المفضلة --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="fw-bold mb-0 text-dark">⚡ الخدمات المبتكرة والمفضلة</h5>
        <button type="button" wire:click="toggleFavoritesManager" class="btn btn-sm btn-outline-danger fw-bold">
            {{ $showFavoritesManager ? '✕ إغلاق الإدارة' : '⭐ إدارة المفضلة' }}
        </button>
    </div>

    @if ($showFavoritesManager)
        <div class="card mb-3 shadow-sm border-danger">
            <div class="card-header bg-danger text-white py-2">
                <h6 class="fw-bold mb-0">➕ إضافة قالب جديد للمفضلة</h6>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end p-2 bg-light rounded-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">نوع الخدمة</label>
                        <input type="text" wire:model="fav_service_type" class="form-control form-control-sm" placeholder="مثال: تصوير وثائق">
                        @error('fav_service_type') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">السعر</label>
                        <input type="number" step="0.01" wire:model="fav_service_price" class="form-control form-control-sm text-center" min="0">
                        @error('fav_service_price') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">الكمية</label>
                        <input type="number" wire:model="fav_service_quantity" class="form-control form-control-sm text-center" min="1" value="1">
                        @error('fav_service_quantity') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2 d-grid">
                        <button wire:click="addFavoriteService" class="btn btn-danger btn-sm fw-bold">➕ إضافة</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (count($favorites_services) > 0)
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-white border-0 py-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-danger">
                    ⭐ خدمات سريعة
                    <span class="small text-muted fw-normal">— اضغط على المربع لتحميله في النموذج فوراً</span>
                </h6>
                <span class="badge bg-danger-subtle text-danger">{{ count($favorites_services) }} عنصر</span>
            </div>
            <div class="card-body pt-0">
                <div class="row g-2">
                    @foreach ($favorites_services as $fav)
                        <div class="col-6 col-sm-4 col-md-3">
                            <div wire:click="useFavoriteService({{ $fav->id }})" class="favorite-card" style="--fav-color: #6f42c1">
                                <div class="favorite-icon">⚙️</div>
                                <div class="favorite-name">{{ $fav->service_type }}</div>
                                <div class="favorite-price">
                                    @if($fav->quantity > 1)
                                        {{ $fav->quantity }} × 
                                    @endif
                                    {{ number_format($fav->price, 2) }} دج
                                </div>
                                <div class="click-overlay"><span>+</span></div>
                                
                                @if($showFavoritesManager)
                                <button type="button" wire:click.stop="deleteFavoriteService({{ $fav->id }})" class="favorite-del-btn" title="حذف من المفضلة">
                                    <span>✕</span>
                                </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-light">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="p-2 bg-primary bg-opacity-10 rounded-3 text-primary">⚙️</div>
                        <h6 class="fw-bold mb-0 text-dark">{{ $is_edit ? 'تعديل بيانات الخدمة' : 'إنشاء وتوثيق خدمة' }}</h6>
                    </div>
                    
                    <form wire:submit.prevent="saveService">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">نوع العمل / الخدمة</label>
                            <input type="text" wire:model.live="service_type" class="form-control border-0 shadow-sm bg-white" required list="services-suggestions" placeholder="اكتب نوع الخدمة المقدمة...">
                            <datalist id="services-suggestions">
                                <option value="كتابة وثائق وبحوث">
                                <option value="تنسيق ملفات وتصاميم">
                                <option value="تسجيل في المنصات">
                                <option value="طباعة وتصوير مستندات">
                            </datalist>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label small fw-bold text-muted">السعر (دج)</label>
                                <input type="number" step="0.01" wire:model.live="price" class="form-control border-0 shadow-sm bg-white fw-bold text-center text-primary fs-5" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold text-muted">الكمية</label>
                                <input type="number" wire:model.live="quantity" class="form-control border-0 shadow-sm bg-white fw-bold text-center fs-5" min="1" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">حساب الزبون المستلم</label>
                            <select wire:model="selected_customer_id" class="form-select border-0 shadow-sm bg-white">
                                <option value="">-- زبون عابر --</option>
                                @foreach(\App\Models\Customer::all()->unique('name') as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted d-block">آلية السداد المالي:</label>
                            <div class="d-flex gap-1 p-1 bg-white rounded-3 shadow-sm">
                                <input type="radio" class="btn-check" name="ser_pay" id="s_full" value="full" wire:model.live="payment_method">
                                <label class="btn btn-sm btn-outline-success border-0 flex-grow-1 fw-bold py-1" for="s_full">كامل</label>

                                <input type="radio" class="btn-check" name="ser_pay" id="s_part" value="partial" wire:model.live="payment_method">
                                <label class="btn btn-sm btn-outline-warning border-0 flex-grow-1 fw-bold py-1" for="s_part">جزئي</label>

                                <input type="radio" class="btn-check" name="ser_pay" id="s_debt" value="debt" wire:model.live="payment_method">
                                <label class="btn btn-sm btn-outline-danger border-0 flex-grow-1 fw-bold py-1" for="s_debt">دين</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">المبلغ النقدي المقبوض فوراَ:</label>
                            <input type="number" step="0.01" wire:model.live="paid_amount" class="form-control border-0 shadow-sm bg-white text-center text-success fw-bold" min="0" {{ $payment_method !== 'partial' ? 'disabled' : '' }}>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold shadow-sm py-2">
                            💾 {{ $is_edit ? 'حفظ التعديلات الجارية' : 'تأكيد وترحيل المعاملة' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <input type="text" wire:model.live="search" class="form-control border-0 shadow-sm rounded-3" style="max-width: 300px;" placeholder="🔍 ابحث في سجل الخدمات...">
                
                {{-- ✅ فلتر التاريخ --}}
                <div class="d-flex gap-2 align-items-center">
                    <div class="d-flex align-items-center gap-1">
                        <label class="small text-muted mb-0">من:</label>
                        <input type="date" wire:model.live="from_date" class="form-control form-control-sm">
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <label class="small text-muted mb-0">إلى:</label>
                        <input type="date" wire:model.live="to_date" class="form-control form-control-sm">
                    </div>
                    @if($from_date || $to_date)
                        <button wire:click="clearDates" class="btn btn-outline-secondary btn-sm">مسح</button>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                @php
                    $baseQuery = Service::with(['customer', 'user'])
                        ->where(function($query) {
                            $query->where('service_type', 'like', '%'.$this->search.'%')
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

                    // حساب المجموع الكلي (الأرباح هنا 100% لأنها خدمات)
                    $sumQuery = clone $baseQuery;
                    $total_sales = (clone $baseQuery)->selectRaw('SUM(price * quantity) as aggregate')->value('aggregate') ?? 0;
                    $total_paid = (clone $baseQuery)->sum('paid_amount');
                    $total_debt = $total_sales - $total_paid;
                    $total_profit = $total_sales; // أرباح الخدمات تعتبر 100%

                    $servicesList = $baseQuery->orderBy('created_at', 'desc')->paginate(6); 
                @endphp

                @forelse($servicesList as $serv)
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 card-service-custom position-relative overflow-hidden" style="border-right: 5px solid {{ $serv->payment_method === 'full' ? '#2ec4b6' : ($serv->payment_method === 'partial' ? '#ff9f1c' : '#e71d36') }} !important;">
                            
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="text-xs text-muted font-monospace">#{{ $serv->id }}</span>
                                        <h5 class="fw-bold text-dark my-1" style="font-size: 1.05rem;">{{ $serv->service_type }}</h5>
                                        <span class="text-xs text-muted">الكمية: <b>{{ $serv->quantity }}</b></span>
                                    </div>
                                    <span class="fs-5 fw-bold font-monospace text-primary">{{ number_format($serv->price * $serv->quantity, 2) }} <span style="font-size: 0.75rem;">دج</span></span>
                                </div>

                                <div class="mb-3 bg-light rounded-3 p-2 text-start">
                                    <div class="text-xs text-muted mb-1">👤 المستفيد: <b class="text-dark">{{ $serv->customer_id ? optional($serv->customer)->name : 'زبون عابر' }}</b></div>
                                    <div class="text-xs text-muted mb-1">🔑 الموظف: <b class="text-secondary">{{ optional($serv->user)->name ?: 'المدير العام' }}</b></div>
                                    <div class="text-xs text-muted">📅 التاريخ: <b class="font-monospace text-dark">{{ $serv->created_at->format('Y-m-d H:i') }}</b></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <div>
                                        @if($serv->payment_method === 'full')
                                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success px-2 py-1 text-xs">🟢 مدفوع</span>
                                        @elseif($serv->payment_method === 'partial')
                                            <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning text-dark px-2 py-1 text-xs">➗ جزئي ({{ number_format($serv->paid_amount, 2) }})</span>
                                        @else
                                            <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger px-2 py-1 text-xs">🔴 دين كامل</span>
                                        @endif
                                    </div>

                                    <div class="d-flex gap-1">
                                        <button wire:click="editService({{ $serv->id }})" class="btn btn-light btn-sm text-primary px-2 py-1 rounded-3 text-xs fw-bold shadow-sm">✏️ تعديل</button>
                                        <button onclick="confirm('هل تريد الحذف؟') || event.stopImmediatePropagation()" wire:click="deleteService({{ $serv->id }})" class="btn btn-light btn-sm text-danger px-2 py-1 rounded-3 text-xs shadow-sm">🗑️ حذف</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-5">
                        <div class="fs-3 mb-2">📥</div>
                        لا توجد خدمات مسجلة مطابقة لمعايير البحث حالياً.
                    </div>
                @endforelse
            </div>

            {{-- ✅ جدول المجموع الكلي للأرباح والمدفوعات --}}
            @if($servicesList->isNotEmpty())
            <div class="p-3 bg-light border-top mt-4 rounded-3 shadow-sm">
                <h6 class="fw-bold text-dark mb-2 text-center">📊 المجموع الكلي للخدمات (حسب الفلتر المطبق)</h6>
                <table class="table table-sm table-bordered table-hover mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>إجمالي الخدمات</th>
                            <th>المحصّل (مدفوع)</th>
                            <th>الديون المترتبة</th>
                            <th>صافي الأرباح (100%)</th>
                        </tr>
                    </thead>
                    <tbody class="font-monospace fw-bold">
                        <tr>
                            <td class="text-success">{{ number_format($total_sales, 2) }} دج</td>
                            <td class="text-primary">{{ number_format($total_paid, 2) }} دج</td>
                            <td class="text-danger">{{ number_format($total_debt, 2) }} دج</td>
                            <td class="text-success bg-success-subtle">{{ number_format($total_profit, 2) }} دج</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif

            <div class="mt-4">
                {{ $servicesList->links() }}
            </div>
        </div>
    </div>
</div>

<style>
    .border-top-custom { border-top: 4px solid #872061; }
    .text-xs { font-size: 0.73rem; }
    .card-service-custom { transition: transform 0.2s ease, box-shadow 0.2s ease; background-color: #fff; }
    .card-service-custom:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }

    .favorite-card {
        position: relative; background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        border: 2px solid var(--fav-color, #872061); border-radius: 12px; padding: 15px 10px;
        text-align: center; cursor: pointer; transition: all 0.2s ease; height: 100%; min-height: 110px;
        display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden;
        user-select: none;
    }
    .favorite-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); border-color: var(--fav-color, #872061); }
    .favorite-card:active { transform: scale(0.97); }
    .favorite-icon { font-size: 1.8rem; margin-bottom: 4px; line-height: 1; }
    .favorite-name { font-size: 0.78rem; font-weight: 700; color: #333; line-height: 1.2; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .favorite-price { font-size: 0.75rem; font-weight: 600; color: var(--fav-color, #872061); font-family: monospace; }
    
    .click-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.7); display: flex; align-items: center; justify-content: center;
        font-size: 3rem; font-weight: bold; color: var(--fav-color, #872061); opacity: 0;
        transition: opacity 0.1s; pointer-events: none;
    }
    .favorite-card:active .click-overlay { opacity: 1; transform: scale(1.2); }

    .favorite-del-btn {
        position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; border-radius: 50%; 
        background: #dc3545; color: #fff; border: none; display: flex; align-items: center; justify-content: center;
        font-size: 0.8rem; font-weight: bold; cursor: pointer; z-index: 2; box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        transition: all 0.15s ease;
    }
    .favorite-del-btn:hover { transform: scale(1.15); }
</style>