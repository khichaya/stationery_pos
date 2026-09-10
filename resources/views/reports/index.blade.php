 @extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark">📊 لوحة القيادة والتقارير المالية الشاملة</h4>
        <p class="text-muted small">كشف تحليلي فوري لـ رأس مال مكتبة السلام، جرد الديون المتداولة، وتتبع نواقص المخزن والخدمات الأكثر مبيعاً.</p>
    </div>

    @livewire('reports-manager')
</div>
@endsection