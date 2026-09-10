 @extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark">🧾 أرشيف المبيعات والفواتير السابقة</h4>
        <p class="text-muted small">هنا يمكنك فحص تاريخ مبيعات الزبائن، معرفة السلع التي اشتروها، وإلغاء الفواتير الخاطئة لإعادة ضبط الرفوف تلقائياً.</p>
    </div>

    @livewire('sales-manager')
</div>
@endsection