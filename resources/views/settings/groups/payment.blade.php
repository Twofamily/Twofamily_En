{{-- แท็บการรับชำระเงิน --}}
<h6 class="fw-semibold">บัญชีรับชำระ</h6>
<p class="text-muted small">แสดงในใบแจ้งหนี้และใบวางบิล ให้ลูกค้าโอนเงิน</p>

<div class="row g-3 mt-2">
    <div class="col-md-4">@include('settings._field', ['key' => 'payment.bank_name'])</div>
    <div class="col-md-4">@include('settings._field', ['key' => 'payment.account_name'])</div>
    <div class="col-md-4">@include('settings._field', ['key' => 'payment.account_no'])</div>
</div>

<hr class="my-4">

<h6 class="fw-semibold">หลักฐานการชำระ</h6>

<div class="row g-3 mt-2">
    <div class="col-12">
        @include('settings._field', [
            'key'  => 'payment.require_slip',
            'hint' => 'เปิดไว้ = ออกใบเสร็จไม่ได้จนกว่าจะแนบสลิปครบยอด',
        ])
    </div>
    <div class="col-md-4">@include('settings._field', ['key' => 'payment.slip_max_kb'])</div>
</div>