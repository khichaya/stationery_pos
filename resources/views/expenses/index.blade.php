 @extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark">💸 إدارة المصاريف اليومية وصندوق المال (الكاس)</h4>
        <p class="text-muted small">توثيق دقيق لكافة المبالغ الخارجة من الصندوق لضمان حساب الأرباح الصافية للمكتبة بدقة متناهية.</p>
    </div>

    <!-- استدعاء المكون الذكي -->
    @livewire('expense-manager')
</div>
@endsection