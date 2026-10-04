{{-- แท็บทั่วไป --}}
<div class="row g-3">
    <div class="col-md-4">
        @include('settings._field', [
            'key'  => 'general.per_page',
            'hint' => 'ใช้กับหน้ารายการทุกหน้า ผู้ใช้ยังเปลี่ยนเองได้ที่หน้านั้น ๆ',
        ])
    </div>
</div>