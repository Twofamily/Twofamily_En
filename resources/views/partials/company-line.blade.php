{{--
    ชื่อบริษัท + เบอร์โทร + ที่อยู่ ใต้หัวเอกสารในหน้าดู (ใบเสนอราคา / ใบสั่งขาย / ใบส่งของ / ใบแจ้งหนี้ / ใบเสร็จ)
    อ่านจากหน้า ตั้งค่าระบบ → ข้อมูลบริษัท แก้ที่เดียวเปลี่ยนทุกหน้า (ชุดเดียวกับที่ PDF ใช้)
--}}
@php
    $companyName  = \App\Models\Setting::get('company.name_th');
    $companyPhone = \App\Models\Setting::get('company.phone');
    $companyAddr  = \App\Models\Setting::get('company.address');
    $companyTax   = \App\Models\Setting::get('company.tax_id');
@endphp

<p class="mb-1">{{ $companyName }}</p>
<p class="text-muted small">
    @if ($companyPhone) โทร: {{ $companyPhone }} @endif
    @if ($companyPhone && $companyAddr) | @endif
    @if ($companyAddr) ที่อยู่: {{ $companyAddr }} @endif
    @if ($companyTax)
        <br>เลขประจำตัวผู้เสียภาษี {{ $companyTax }}
    @endif
</p>
