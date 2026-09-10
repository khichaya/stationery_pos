@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm border-right-custom">
        <div>
            <h4 class="fw-bold text-dark mb-1">🛒 شاشة كاشير المبيعات</h4>
            <p class="text-muted small mb-0">قم بتمرير باركود السلعة أو إدخال البيانات لإصدار الفاتورة فورياً.</p>
        </div>
        <div>
            <span class="badge bg-success p-2 fs-6 shadow-sm">🟢 النظام متصل محلياً</span>
        </div>
    </div>

    <livewire:pos />
</div>

<style>
    /* لمسة جمالية تتوافق مع هوية الشعار (الأرجواني) على حافة العنوان */
    .border-right-custom {
        border-right: 5px solid #872061;
    }
</style>
@endsection