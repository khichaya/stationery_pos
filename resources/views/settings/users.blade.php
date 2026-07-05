@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark">🛡️ تسيير طاقم العمل والصلاحيات الأمنية</h4>
        <p class="text-muted small">بصفتك المدير العام، يمكنك توزيع الأدوار والصلاحيات على العمال لضمان سير صندوق الكاس والمخزن بأعلى مستويات الحماية والرقابة.</p>
    </div>

    @livewire('users-management')
</div>
@endsection