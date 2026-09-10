 @extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">👥 إدارة حسابات الزبائن والديون الجارية</h4>
            <p class="text-muted small mb-0">يمكنك من هنا متابعة مستحقات مكتبة السلام بالخارج، جدولة الديون، واستلام الدفعات النقدية.</p>
        </div>
    </div>

    @livewire('customer-manager')
</div>
@endsection