{{-- แท็บข้อมูลบริษัท : แสดงที่หัวเอกสาร PDF ทุกประเภท --}}
<p class="text-muted">ข้อมูลนี้ใช้แสดงที่หัวเอกสาร PDF ทุกประเภท</p>

<div class="row g-3 mt-2">
    <div class="col-md-6">@include('settings._field', ['key' => 'company.name_th'])</div>
    <div class="col-md-6">@include('settings._field', ['key' => 'company.name_en'])</div>

    <div class="col-md-6">
        @include('settings._field', ['key' => 'company.tax_id', 'hint' => 'พิมพ์มีขีดหรือไม่มีก็ได้ ระบบจะเก็บเป็นตัวเลข 13 หลัก'])
    </div>
    <div class="col-md-6">@include('settings._field', ['key' => 'company.branch'])</div>

    <div class="col-12">@include('settings._field', ['key' => 'company.address'])</div>

    <div class="col-md-6">@include('settings._field', ['key' => 'company.phone'])</div>
    <div class="col-md-6">@include('settings._field', ['key' => 'company.email'])</div>

    <div class="col-md-6">@include('settings._field', ['key' => 'company.logo'])</div>
</div>