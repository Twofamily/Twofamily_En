@php
    $noSlipMethods = \App\Models\Payment::METHODS_WITHOUT_SLIP;
    $slipRequired  = $requireSlip && ! in_array($method, $noSlipMethods, true);
@endphp

<div class="mb-3" data-slip-box>
    <label for="slip" class="form-label">
        หลักฐานการชำระ
        <span class="text-danger" data-slip-star @if (! $slipRequired) hidden @endif>*</span>
        <small class="text-muted fw-normal" data-slip-optional @if ($slipRequired) hidden @endif>(ไม่บังคับ)</small>
    </label>
    <input type="file" id="slip" name="slip" class="form-control"
           accept=".jpg,.jpeg,.png,.pdf" @required($slipRequired)
           data-required-msg="กรุณาแนบหลักฐานการชำระ (สลิปโอน / รูปเช็ค)">
    <div class="form-text" data-slip-hint-file @if (! $slipRequired && $requireSlip) hidden @endif>
        สลิปโอน / รูปเช็ค — JPG, PNG หรือ PDF ไม่เกิน 5 MB
    </div>
    <div class="form-text text-success" data-slip-hint-cash @if ($slipRequired || ! $requireSlip) hidden @endif>
        <i class="bi bi-person-check me-1"></i>
        รับเงินสด: ระบบบันทึกผู้รับเงินเป็น <strong>{{ auth()->user()->name }}</strong> ให้อัตโนมัติ
        — แนบรูปใบรับเงินที่ลูกค้าเซ็นได้ถ้ามี
    </div>
</div>

@if ($requireSlip)
    <script>
        (() => {
            const box    = document.querySelector('[data-slip-box]');
            const form   = box.closest('form');
            const method = form.querySelector('#method');
            const input  = box.querySelector('#slip');
            const noSlip = @json($noSlipMethods);

            function sync() {
                const required = !noSlip.includes(method.value);
                input.required = required;
                box.querySelector('[data-slip-star]').hidden      = !required;
                box.querySelector('[data-slip-optional]').hidden  = required;
                box.querySelector('[data-slip-hint-file]').hidden = !required;
                box.querySelector('[data-slip-hint-cash]').hidden = required;
                if (!required) {
                    input.classList.remove('is-invalid');
                    const msg = box.querySelector('.fv-msg');
                    if (msg) msg.classList.remove('d-block');
                }
            }

            method.addEventListener('change', sync);
            sync();
        })();
    </script>
@endif
