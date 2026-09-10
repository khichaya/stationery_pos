<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

new class extends Component
{
    use WithFileUploads;

    public $users;
    public $user_id, $name, $email, $password, $role = 'staff';
    public $selected_permissions = [];
    public $is_edit = false;

  public $available_permissions = [
    'access_dashboard'     => 'الوصول للوحة التحكم الرئيسية (dashboard)', // 👈 الصلاحية الجديدة
    'view_dashboard_stats' => 'رؤية إحصائيات الأرباح والرسوم البيانية الكلية',
    'access_pos'           => 'الوصول لشاشة نقطة البيع (POS)',
    'manage_products'      => 'إدارة المخزن والمنتجات (إضافة/تعديل)',
    'manage_services'      => 'إدارة الخدمات الإلكترونية والبحوث',
    'manage_expenses'      => 'تسجيل وتسيير المصاريف اليومية',
    'manage_debts'         => 'التحكم في ديون وكشوفات الزبائن',
    'database_backup'      => 'الوصول للنسخ الاحتياطي والاسترجاع',
];

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $this->users = User::all();
    }

    public function updatedRole($value)
    {
        if ($value === 'manager') {
    $this->selected_permissions = array_fill_keys(array_keys($this->available_permissions), true);
} else if ($value === 'cashier') {
            $this->selected_permissions = [
                'access_pos' => true,
                'manage_services' => true,
            ];
        } else {
            $this->selected_permissions = [];
        }
    }

    public function resetFields()
    {
        $this->reset(['user_id', 'name', 'email', 'password', 'role', 'selected_permissions', 'is_edit']);
    }

    public function saveUser()
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user_id,
            'role'  => 'required',
            'password' => $this->is_edit ? 'nullable|min:6' : 'required|min:6',
        ]);

        $permissionsToSave = array_keys(array_filter($this->selected_permissions));

        $data = [
            'name'        => $this->name,
            'email'       => $this->email,
            'role'        => $this->role,
            'permissions' => json_encode($permissionsToSave),
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(['id' => $this->user_id], $data);

        session()->flash('success', $this->is_edit ? '🎯 تم تحديث بيانات الموظف وصلاحياته بنجاح!' : '👤 تم إضافة الموظف الجديد وتعيين صلاحياته بنجاح!');
        
        $this->resetFields();
        $this->loadUsers();
    }

    public function editUser($id)
    {
        $this->resetFields();
        $user = User::findOrFail($id);
        
        $this->user_id = $user->id;
        $this->name    = $user->name;
        $this->email   = $user->email;
        $this->role    = $user->role;
        $this->is_edit = true;

        $oldPermissions = is_array($user->permissions) ? $user->permissions : (json_decode($user->permissions, true) ?? []);
        foreach ($oldPermissions as $perm) {
            $this->selected_permissions[$perm] = true;
        }
    }

    public function deleteUser($id)
    {
        if ($id == auth()->id()) {
            session()->flash('error', '❌ لا يمكنك حذف حسابك الحالي الذي تسجل به الدخول!');
            return;
        }

        User::destroy($id);
        session()->flash('success', '🗑️ تم حذف المستخدم من النظام بنجاح.');
        $this->loadUsers();
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success p-2 small fw-bold mb-3">✨ {{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger p-2 small fw-bold mb-3">❌ {{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-person-plus-fill text-primary me-1"></i> 
                        {{ $is_edit ? 'تعديل صلاحيات الموظف' : 'إضافة موظف جديد لـ بيان' }}
                    </h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="saveUser">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">👤 اسم الموظف الكلي</label>
                            <input type="text" wire:model="name" class="form-control form-control-sm" placeholder="مثال: محمد سحراوي" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">✉️ البريد الإلكتروني (اسم الدخول)</label>
                            <input type="email" wire:model="email" class="form-control form-control-sm placeholder-xs" placeholder="username@gmail.com" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold">🔒 كلمة المرور</label>
                            <input type="password" wire:model="password" class="form-control form-control-sm" placeholder="{{ $is_edit ? 'اتركها فارغة لعدم التغيير' : '6 أحرف أو أرقام على الأقل' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">💼 الدور الوظيفي الأساسي</label>
                            <select wire:model.live="role" class="form-select form-select-sm fw-bold text-secondary">
                                <option value="staff">عامل عادي (Staff)</option>
                                <option value="cashier">مكلف بالـ POS والكاس (Cashier)</option>
                                <option value="manager">مدير عام ذو صلاحيات واسعة (Manager)</option>
                            </select>
                        </div>

                        <div class="mb-3 border-top pt-2">
                            <label class="form-label small fw-bold text-primary mb-2"><i class="bi bi-shield-check"></i> تخصيص الصلاحيات الدقيقة:</label>
                            <div class="bg-light p-2 rounded-3" style="max-height: 220px; overflow-y: auto;">
                                @foreach($available_permissions as $key => $label)
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model.live="selected_permissions.{{ $key }}" id="perm_{{ $key }}">
                                        <label class="form-check-label text-dark" for="perm_{{ $key }}" style="font-size: 0.78rem;">
                                            {{ $label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> {{ $is_edit ? 'تحديث الصلاحيات' : 'حفظ الموظف والتمكين' }}
                            </button>
                            @if($is_edit)
                                <button type="button" wire:click="resetFields" class="btn btn-secondary btn-sm fw-bold">إلغاء</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-secondary me-1"></i> طاقم العمل المسجل بنظام بيان للمكتبة</h6>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>الأفاتار</th>
                                <th>الاسم والبريد</th>
                                <th>الدور الوظيفي</th>
                                <th class="text-start">الصلاحيات المفتوحة له</th>
                                <th>الخيارات الإدارية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                                <tr class="{{ $u->id == auth()->id() ? 'table-primary-subtle' : '' }}">
                                    <td>
                                        <span class="avatar bg-secondary text-light d-flex align-items-center justify-content-center rounded-circle mx-auto fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                            {{ mb_substr($u->name, 0, 1) }}
                                        </span>
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $u->name }}</div>
                                        <div class="text-muted font-monospace" style="font-size: 0.75rem;">{{ $u->email }}</div>
                                    </td>
                                    <td>
                                        @if($u->role === 'manager')
                                            <span class="badge bg-danger-subtle text-danger fw-bold">مدير عام</span>
                                        @elseif($u->role === 'cashier')
                                            <span class="badge bg-success-subtle text-success fw-bold">كاشير / مبيعات</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">موظف عادي</span>
                                        @endif
                                    </td>
                                    <td class="text-start" style="max-width: 250px;">
                                        @php 
                                            $userPerms = is_array($u->permissions) ? $u->permissions : (json_decode($u->permissions, true) ?? []);
                                        @endphp
                                        @forelse($userPerms as $p)
                                            <span class="badge bg-light text-dark border mb-1" style="font-size: 0.7rem;">
                                                {{ $available_permissions[$p] ?? $p }}
                                            </span>
                                        @empty
                                            <span class="text-muted italic" style="font-size: 0.75rem;">لا يملك أي صلاحيات فرعية (حساب مجمد)</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="editUser({{ $u->id }})" class="btn btn-outline-primary py-0 px-2" title="تعديل"><i class="bi bi-pencil-square"></i></button>
                                            <button wire:click="deleteUser({{ $u->id }})" class="btn btn-outline-danger py-0 px-2" onclick="return confirm('هل أنت متأكد من سحب وحذف هذا الحساب نهائياً من نظام بيان؟')" title="حذف"><i class="bi bi-trash shadow-sm"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .placeholder-xs::placeholder { font-size: 0.75rem; }
</style>