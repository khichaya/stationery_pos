<?php

use Livewire\Component;

new class extends Component
{
    // لا حاجة لأي منطق هنا - مجرد إعادة رسم فارغة كل دقيقة
    // كافية لتوليد طلب AJAX يمر عبر الـ middleware ويشغّل فحص الجدولة
};
?>

<div wire:poll.60000ms style="display:none"></div>