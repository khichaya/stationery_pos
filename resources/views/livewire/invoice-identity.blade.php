<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $setting_id = null;
    public $name = '';
    public $manager_name = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $nif = '';
    public $nis = '';
    public $rc = '';
    public $ai = '';
    public $invoice_footer = '';
    public $logo;
    public $existing_logo = '';

    public function mount()
    {
        $setting = InstitutionSetting::first();
        if ($setting) {
            $this->fillSetting($setting);
        }
    }

    private function fillSetting($setting)
    {
        $this->setting_id = $setting->id;
        $this->name = $setting->name ?? '';
        $this->manager_name = $setting->manager_name ?? '';
        $this->phone = $setting->phone ?? '';
        $this->email = $setting->email ?? '';
        $this->address = $setting->address ?? '';
        $this->nif = $setting->nif ?? '';
        $this->nis = $setting->nis ?? '';
        $this->rc = $setting->rc ?? '';
        $this->ai = $setting->ai ?? '';
        $this->invoice_footer = $setting->invoice_footer ?? '';
        $this->existing_logo = $setting->logo_path ?? '';
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|max:1024',
        ]);

        $logoPath = $this->existing_logo;
        if ($this->logo) {
            $logoPath = $this->logo->store('institution', 'public');
        }

        InstitutionSetting::updateOrCreate(
            ['id' => $this->setting_id],
            [
                'name' => $this->name,
                'manager_name' => $this->manager_name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'nif' => $this->nif,
                'nis' => $this->nis,
                'rc' => $this->rc,
                'ai' => $this->ai,
                'invoice_footer' => $this->invoice_footer,
                'logo_path' => $logoPath,
            ]
        );

        $this->existing_logo = $logoPath;
        $this->logo = null;

        session()->flash('success', '✨ تم حفظ بيانات المؤسسة بنجاح!');
    }
};
?>

<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show p-2 small fw-bold mb-3" role="alert">
            ✨ {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-top: 4px solid #872061; border-radius: 12px;">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">⚙️ إعدادات هوية المؤسسة وهيدر الفواتير</h6>
                </div>
                
                <div class="card-body p-4">
                    <form wire:submit.prevent="save">
                        <div class="row g-3">

                            {{-- الشعار --}}
                            <div class="col-md-4 text-center border-start ps-md-4">
                                <label class="form-label small fw-bold d-block text-end mb-2">شعار المؤسسة</label>
                                <div class="mx-auto mb-2 border rounded-3 p-2 bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 140px; height: 140px; border-style: dashed !important; cursor: pointer;"
                                     onclick="document.getElementById('logoInput').click()">
                                    @if ($logo)
                                        <img src="{{ $logo->temporaryUrl() }}" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                                    @elseif ($existing_logo)
                                        <img src="{{ Storage::url($existing_logo) }}" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                                    @else
                                        <div class="text-muted text-center">
                                            <i class="bi bi-building fs-1"></i>
                                            <div class="small mt-1">بدون شعار</div>
                                        </div>
                                    @endif
                                </div>
                                <input type="file" wire:model="logo" id="logoInput" class="d-none" accept="image/*">
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="document.getElementById('logoInput').click()">
                                    <i class="bi bi-upload"></i> اختيار صورة
                                </button>
                                <span wire:loading wire:target="logo" class="text-primary small d-block mt-1">⏳ جاري المعالجة...</span>
                                @error('logo') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- البيانات --}}
                            <div class="col-md-8">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">اسم المؤسسة <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="name" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">اسم المدير</label>
                                        <input type="text" wire:model="manager_name" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">الهاتف</label>
                                        <input type="text" wire:model="phone" class="form-control form-control-sm text-start" placeholder="06xxxxxxxx">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">البريد</label>
                                        <input type="email" wire:model="email" class="form-control form-control-sm text-start">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">العنوان</label>
                                        <input type="text" wire:model="address" class="form-control form-control-sm" placeholder="مثال: وسط المدينة، قمار - الوادي">
                                    </div>
                                </div>
                            </div>

                            <hr class="text-muted my-2">

                            {{-- الوثائق الضريبية --}}
                            <div class="col-12">
                                <label class="form-label small fw-bold text-primary mb-2">📋 الوثائق الضريبية</label>
                                <div class="row g-2">
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small text-muted">NIF</label>
                                        <input type="text" wire:model="nif" class="form-control form-control-sm font-monospace">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small text-muted">NIS</label>
                                        <input type="text" wire:model="nis" class="form-control form-control-sm font-monospace">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small text-muted">RC</label>
                                        <input type="text" wire:model="rc" class="form-control form-control-sm font-monospace">
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label small text-muted">Article</label>
                                        <input type="text" wire:model="ai" class="form-control form-control-sm font-monospace">
                                    </div>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="col-12">
                                <label class="form-label small fw-bold">📝 تذييل الفاتورة</label>
                                <input type="text" wire:model="invoice_footer" class="form-control form-control-sm" placeholder="نص يظهر أسفل كل فاتورة">
                            </div>

                            {{-- زر الحفظ --}}
                            <div class="col-12 text-start mt-2">
                                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                                    💾 حفظ البيانات
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>