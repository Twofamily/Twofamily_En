{{-- ลายน้ำ "ยกเลิก" บน PDF ของเอกสารที่ยกเลิกแล้ว (ใส่ไว้ต้น <body>) --}}
@if ($doc->isCancelled())
    <div style="position: fixed; top: 38%; left: 0; right: 0; text-align: center;
                font-size: 110px; font-weight: bold; color: #dc3545; opacity: 0.18;
                transform: rotate(-30deg); z-index: 1000;">
        ยกเลิก
    </div>
    <div style="border: 1px solid #dc3545; color: #dc3545; padding: 4px 8px; margin-bottom: 6px; font-size: 16px;">
        เอกสารนี้ถูกยกเลิกเมื่อ {{ $doc->cancelled_at?->format('d/m/Y') }}
        @if ($doc->cancel_reason) — {{ $doc->cancel_reason }} @endif
    </div>
@endif
