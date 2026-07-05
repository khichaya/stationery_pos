 @extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark">🛠️ إدارة وجرد الخدمات الإلكترونية والبحوث</h4>
        <p class="text-muted small">من هنا يمكنك توثيق كافة أعمال البحوث، تنسيق النصوص، والخدمات المكتبية غير السلعية، وتتبع المقبوضات والكريدي المترتب عليها.</p>
    </div>

    @livewire('service-manager')
</div>
@endsection