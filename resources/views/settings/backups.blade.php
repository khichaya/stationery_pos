@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark">🛡️ إدارة النسخ الاحتياطي والأرشفة السحابية لقاعدة البيانات</h4>
        <p class="text-muted small">هنا يمكنك ضبط جدار الأمان المالي والسلعي لنظام بيان، وإنشاء نسخ احتياطية دورية مؤتمتة أو يدوية لحماية أرقامك.</p>
    </div>

    <!-- استدعاء المكون الذكي -->
    @livewire('backup-manager')
</div>
@endsection