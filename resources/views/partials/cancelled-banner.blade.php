{{--
    แถบแจ้งว่าเอกสารถูกยกเลิกแล้ว (ใช้ในหน้าดูใบส่งของ / ใบแจ้งหนี้ / ใบเสร็จ)
    @include('partials.cancelled-banner', ['doc' => $invoice, 'label' => 'ใบแจ้งหนี้'])
--}}
@if ($doc->isCancelled())
    <div class="alert alert-dark d-flex gap-3 align-items-start mb-3" role="alert">
        <i class="bi bi-x-octagon-fill fs-3 lh-1"></i>
        <div>
            <div class="fw-bold">{{ $label }}นี้ถูกยกเลิกแล้ว — ไม่นับในยอดใด ๆ</div>
            <div class="small">
                ยกเลิกเมื่อ {{ $doc->cancelled_at?->format('d/m/Y H:i') }}
                @if ($doc->cancelledBy)
                    โดย {{ $doc->cancelledBy->name }}
                @endif
            </div>
            @if ($doc->cancel_reason)
                <div class="small mt-1">เหตุผล: {{ $doc->cancel_reason }}</div>
            @endif
        </div>
    </div>
@endif
