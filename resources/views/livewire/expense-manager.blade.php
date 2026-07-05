<?php

use Livewire\Component;
use App\Models\Expense;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';

    // المتغيرات المتوافقة مع جدولك
    public $expense_id, $amount = 0, $description;
    public $is_edit = false;

    public function resetFields()
    {
        $this->reset(['expense_id', 'amount', 'description', 'is_edit']);
    }

    public function saveExpense()
    {
        $this->validate([
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
        ], [
            'description.required' => 'يرجى كتابة تفاصيل المصروف (اسم الشخص والسبب).',
            'amount.required' => 'يرجى تحديد قيمة المبلغ المسحوب.',
        ]);

        Expense::updateOrCreate(
            ['id' => $this->expense_id],
            [
                'amount' => $this->amount,
                'description' => $this->description,
                'user_id' => auth()->id() ?? 1,
            ]
        );

        session()->flash('success', $this->is_edit ? '✨ تم تحديث بيانات المصروف بنجاح!' : '✨ تم تسجيل المصروف وخصمه من الكاس بنجاح!');
        $this->resetFields();
    }

    public function editExpense($id)
    {
        $expense = Expense::findOrFail($id);
        $this->expense_id = $expense->id;
        $this->amount = $expense->amount;
        $this->description = $expense->description;
        $this->is_edit = true;
    }

    public function deleteExpense($id)
    {
        Expense::findOrFail($id)->delete();
        session()->flash('success', '🗑️ تم حذف سجل المصروف بنجاح.');
    }

    public function rendering()
    {
        if ($this->search) { $this->resetPage(); }
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success p-2 small fw-bold mb-3">✨ {{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark">{{ $is_edit ? '📝 تعديل قيد المصروف' : '💸 تسجيل مخرج من الكاس (مصروف)' }}</h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="saveExpense">
                        
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-danger">💰 القيمة المأخوذة من الكاس (دج) *</label>
                            <input type="number" step="0.01" wire:model.live="amount" class="form-control form-control-sm font-monospace text-center fw-bold text-danger fs-5" required min="0.01">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">📝 تفاصيل وبيان المصروف *</label>
                            <textarea wire:model.live="description" class="form-control form-control-sm text-start" rows="4" required placeholder="اكتب هنا بالتفصيل.. مثال: سحب مبلغ 500 دج من طرف العامل يحيى لفائدة فطور العمال، أو تسديد فاتورة الكهرباء..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger btn-sm flex-grow-1 fw-bold shadow-sm">
                                💾 {{ $is_edit ? 'تحديث المصروف' : 'تأكيد السحب والتسجيل' }}
                            </button>
                            @if($is_edit)
                                <button type="button" wire:click="resetFields" class="btn btn-secondary btn-sm">إلغاء</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <input type="text" wire:model.live="search" class="form-control form-control-sm w-50" placeholder="🔍 ابحث في تفاصيل وبيانات المصاريف...">
                    <div class="text-muted small fw-bold">إجمالي الخارج اليوم: <span class="text-danger font-monospace">{{ number_format(\App\Models\Expense::wheredate('created_at', today())->sum('amount'), 2) }} دج</span></div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th># الرقم</th>
                                <th class="text-start" style="width: 45%;">📝 تفاصيل وبيان المصروف الحالي</th>
                                <th>💰 المبلغ المسحوب</th>
                                <th>📅 التاريخ والوقت</th>
                                <th>الخيارات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $expensesList = Expense::where('description', 'like', '%'.$this->search.'%')
                                    ->orderBy('created_at', 'desc')
                                    ->paginate(10);
                            @endphp

                            @forelse($expensesList as $exp)
                                <tr>
                                    <td class="text-muted font-monospace">#{{ $exp->id }}</td>
                                    <td class="text-start text-dark fw-bold bg-light-subtle p-2">
                                        <div style="font-size: 0.85rem; max-height: 60px; overflow-y: auto;">
                                            {{ $exp->description }}
                                        </div>
                                    </td>
                                    <td class="font-monospace fw-bold text-danger fs-6">{{ number_format($exp->amount, 2) }} دج</td>
                                    <td class="text-muted font-monospace">{{ $exp->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button wire:click="editExpense({{ $exp->id }})" class="btn btn-warning btn-sm px-1 py-0 text-dark text-xs fw-bold shadow-sm">✏️ تعديل</button>
                                            <button onclick="confirm('هل أنت متأكد من حذف هذا المصروف؟') || event.stopImmediatePropagation()" wire:click="deleteExpense({{ $exp->id }})" class="btn btn-outline-danger btn-sm px-1 py-0 text-xs">🗑️ حذف</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted p-4">✅ الكاس متوازن، لم يتم تسجيل أي مصاريف مطابقة للبحث اليوم.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white pt-2 border-0">
                    {{ $expensesList->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-xs { font-size: 0.75rem; }
</style>