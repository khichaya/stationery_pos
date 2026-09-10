<?php

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    
    // متغيرات النموذج المحدثة
    public $customer_id, $name, $phone, $total_debt = 0, $observation; 
    public $payment_amount = 0;
    public $selected_customer_for_payment;
    public $is_edit = false;

    public function resetFields()
    {
        $this->reset(['customer_id', 'name', 'phone', 'total_debt', 'observation', 'payment_amount', 'is_edit', 'selected_customer_for_payment']);
    }

    public function saveCustomer()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'total_debt' => 'required|numeric',
            'observation' => 'nullable|string|max:1000', // تحقق من الملاحظة
        ], [
            'name.required' => 'اسم الزبون حقل إجباري.',
        ]);

        Customer::updateOrCreate(
            ['id' => $this->customer_id],
            [
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'total_debt' => $this->total_debt ?: 0,
                'observation' => $this->observation ?: null, // حفظ الملاحظة
            ]
        );

        session()->flash('success', $this->is_edit ? 'تم تحديث بيانات الزبون والملاحظات بنجاح!' : 'تم تسجيل الزبون بنجاح!');
        $this->resetFields();
    }

    public function editCustomer($id)
    {
        $customer = Customer::findOrFail($id);
        $this->customer_id = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->total_debt = $customer->total_debt;
        $this->observation = $customer->observation; // جلب الملاحظة عند التعديل
        $this->is_edit = true;
    }

    public function openPaymentModal($id)
    {
        $this->selected_customer_for_payment = Customer::findOrFail($id);
        $this->payment_amount = 0;
    }

    public function submitPayment()
    {
        $maxPayment = $this->selected_customer_for_payment->total_debt > 0 ? $this->selected_customer_for_payment->total_debt : 999999;
        
        $this->validate([
            'payment_amount' => 'required|numeric|min:0.01|max:' . $maxPayment,
        ], [
            'payment_amount.max' => 'المبلغ المدفوع يتجاوز المستحقات الحالية للزبون!',
            'payment_amount.min' => 'يرجى إدخال مبلغ صحيح.',
        ]);

        $customer = Customer::find($this->selected_customer_for_payment->id);
        $customer->decrement('total_debt', $this->payment_amount);

        $this->dispatch('close-modal', modalId: 'paymentModal');
        session()->flash('success', 'تم تسجيل تسديد بقيمة ' . number_format($this->payment_amount, 2) . ' دج بنجاح!');
        $this->resetFields();
    }

    public function deleteCustomer($id)
    {
        $customer = Customer::findOrFail($id);
        if ($customer->total_debt > 0) {
            session()->flash('error', 'لا يمكن حذف زبون متبقي عليه ديون غير مسددة للمكتبة!');
            return;
        }
        $customer->delete();
        session()->flash('success', 'تم حذف حساب الزبون.');
    }

    public function rendering()
    {
        if ($this->search) { $this->resetPage(); }
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show p-2 small fw-bold" role="alert">
            ✨ {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show p-2 small fw-bold" role="alert">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-top-custom">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        {{ $is_edit ? '📝 تعديل الحساب المالي' : '👤 تسجيل زبون وحساب جديد' }}
                    </h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="saveCustomer">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">اسم الزبون بالكامل <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" class="form-control form-control-sm" required placeholder="مثال: أحمد بن محمد">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">رقم الهاتف</label>
                            <input type="text" wire:model="phone" class="form-control form-control-sm text-start" placeholder="06xxxxxxxx">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">📊 الرصيد الابتدائي (دج)</label>
                            <input type="number" step="0.01" wire:model="total_debt" class="form-control form-control-sm fw-bold text-center" {{ $is_edit ? 'disabled' : '' }} placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">📝 ملاحظات وتفاصيل الدين</label>
                            <textarea wire:model="observation" class="form-control form-control-sm text-start" rows="3" placeholder="اكتب هنا تفاصيل السلعة أو شروط وأوقات التسديد المتفق عليها..."></textarea>
                            @error('observation') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-bold">
                                💾 {{ $is_edit ? 'تحديث الحساب والملاحظة' : 'حفظ زبون جديد' }}
                            </button>
                            @if($is_edit)
                                <button type="button" wire:click="resetFields" class="btn btn-secondary btn-sm">إلغاء</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3 border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #1e1e2f, #3f3f5f);">
                <div class="card-body p-3">
                    <span class="small fw-bold opacity-75 d-block mb-1">📋 مجموع الدين العام (المستحقات الخارجية):</span>
                    <h3 class="fw-bold mb-3 text-warning font-monospace">{{ number_format(\App\Models\Customer::where('total_debt', '>', 0)->sum('total_debt'), 2) }} دج</h3>
                    
                    <div class="border-top border-secondary pt-2 mt-2 row g-1 text-center small">
                        <div class="col-6 border-end border-secondary">
                            <span class="text-xs opacity-75 d-block">🔴 مجموع الكريدي:</span>
                            <span class="fw-bold text-danger font-monospace">{{ number_format(\App\Models\Customer::where('total_debt', '>', 0)->sum('total_debt'), 2) }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-xs opacity-75 d-block">🟢 مجموع الفضلات:</span>
                            <span class="fw-bold text-success font-monospace">{{ number_format(\App\Models\Customer::where('total_debt', '<', 0)->sum('total_debt') * -1, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <input type="text" wire:model.live="search" class="form-control form-control-sm w-50" placeholder="🔍 ابحث باسم الزبون أو الهاتف أو الملاحظة...">
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>الاسم وهاتف الزبون</th>
                                <th class="fw-bold">الرصيد المالي</th>
                                <th>تحديد نوع الدين</th>
                                <th class="text-start" style="width: 25%;">📝 تفاصيل وملاحظة الدين</th>
                                <th>📅 آخر حركة</th>
                                <th>الخيارات والتسديد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $customersList = Customer::when($this->search, function($query) {
                                        $query->where('name', 'like', '%'.$this->search.'%')
                                              ->orWhere('phone', 'like', '%'.$this->search.'%')
                                              ->orWhere('observation', 'like', '%'.$this->search.'%'); // البحث يشمل الملاحظات أيضاً تلقائياً
                                    })
                                    ->orderBy('total_debt', 'desc')
                                    ->paginate(10);
                            @endphp

                            @forelse($customersList as $customer)
                                <tr style="{{ $customer->total_debt > 0 ? 'background-color: #fff8f8;' : ($customer->total_debt < 0 ? 'background-color: #f4fff4;' : '') }}">
                                    <td class="text-start p-2">
                                        <div class="fw-bold text-dark">{{ $customer->name }}</div>
                                        <div class="text-xs text-muted font-monospace">📱 {{ $customer->phone ?: '--' }}</div>
                                    </td>
                                    <td class="fw-bold font-monospace fs-6 {{ $customer->total_debt > 0 ? 'text-danger' : ($customer->total_debt < 0 ? 'text-success' : 'text-muted') }}">
                                        {{ number_format(abs($customer->total_debt), 2) }} دج
                                    </td>
                                    <td>
                                        @if($customer->total_debt > 0)
                                            <span class="badge bg-danger">🔴 عليه دين</span>
                                        @elseif($customer->total_debt < 0)
                                            <span class="badge bg-success">🟢 له فضلة</span>
                                        @else
                                            <span class="badge bg-secondary">⚪ خالص وسليم</span>
                                        @endif
                                    </td>
                                    
                                    <td class="text-start text-muted bg-light-subtle p-2 small">
                                        @if($customer->observation)
                                            <div class="text-dark border-start border-2 border-warning ps-2 py-1" style="font-size: 0.82rem; max-height: 55px; overflow-y: auto;">
                                                {{ $customer->observation }}
                                            </div>
                                        @else
                                            <span class="text-black-50 text-xs">-- لا توجد ملاحظات --</span>
                                        @endif
                                    </td>

                                    <td class="font-monospace text-muted">
                                        {{ $customer->updated_at ? $customer->updated_at->format('Y-m-d H:i') : ($customer->created_at ? $customer->created_at->format('Y-m-d H:i') : '--') }}
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if($customer->total_debt > 0)
                                                <button wire:click="openPaymentModal({{ $customer->id }})" data-bs-toggle="modal" data-bs-target="#paymentModal" class="btn btn-sm btn-success px-2 py-0 fw-bold">💵 قبض دفعة</button>
                                            @endif
                                            <button wire:click="editCustomer({{ $customer->id }})" class="btn btn-sm btn-outline-primary px-1 py-0">✏️</button>
                                            <button wire:click="deleteCustomer({{ $customer->id }})" onclick="confirm('حذف حساب الزبون نهائياً؟') || event.stopImmediatePropagation()" class="btn btn-sm btn-outline-danger px-1 py-0">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted p-4">لا يوجد زبائن مطابقين للبحث.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white pt-2">
                    {{ $customersList->links() }}
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="fw-bold mb-0">💵 قبض دفعة تسديد الكريدي</h6>
                </div>
                <form wire:submit.prevent="submitPayment">
                    <div class="modal-body py-3 text-center">
                        @if($selected_customer_for_payment)
                            <div class="small text-muted">اسم الزبون:</div>
                            <div class="fw-bold text-dark fs-6">{{ $selected_customer_for_payment->name }}</div>
                            <div class="badge bg-danger mt-1 mb-3">إجمالي الدين عليه: {{ number_format($selected_customer_for_payment->total_debt, 2) }} دج</div>
                            
                            <div class="mb-1 text-start">
                                <label class="form-label small fw-bold">المبلغ المستلم نقداً (دج)</label>
                                <input type="number" step="0.01" wire:model="payment_amount" class="form-control text-center fw-bold border-success text-success fs-4" required placeholder="0.00">
                                @error('payment_amount') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer p-2 d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm flex-grow-1" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success btn-sm flex-grow-1 fw-bold">💾 تأكيد التسديد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
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