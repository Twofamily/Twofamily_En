{{--
    ช่องธนาคาร — ใช้ร่วมกันในหน้าใบแจ้งหนี้ (บันทึกการชำระเงิน) และหน้าต่างออกใบเสร็จขายสด

    เลือก "โอนเงิน" → เติมธนาคารของบัญชีรับเงินที่ตั้งไว้ใน ตั้งค่า > การรับชำระเงิน ให้เอง (แก้ได้)
    เลือก "เช็ค"    → ล้างค่าที่เติมให้ออก (ช่องนี้ใช้กรอกธนาคารของเช็คแทน)
    เลือก "เงินสด"  → ซ่อนช่องนี้ (ไม่มีธนาคาร)

    ต้องมี <select id="method"> อยู่ในฟอร์มเดียวกัน
    ส่ง: $method (วิธีชำระที่เลือกอยู่)
--}}
@php
    $companyBank = trim((string) \App\Models\Setting::get('payment.bank_name', ''));
    $accountName = trim((string) \App\Models\Setting::get('payment.account_name', ''));
    $accountNo   = trim((string) \App\Models\Setting::get('payment.account_no', ''));

    // ค่าเริ่มต้น: ค่าที่กรอกไว้ก่อนเด้งกลับ > ธนาคารบริษัท (ถ้าเลือกโอนเงิน)
    $bankValue = old('bank_name', $method === 'transfer' ? $companyBank : '');
@endphp

<div class="mb-3" data-noncash data-bank-box @if ($method === 'cash') hidden @endif>
    <label for="bank_name" class="form-label">
        ธนาคาร
        <small class="text-muted fw-normal" data-bank-label-transfer @if ($method !== 'transfer') hidden @endif>(บัญชีที่รับโอน)</small>
        <small class="text-muted fw-normal" data-bank-label-cheque @if ($method !== 'cheque') hidden @endif>(ธนาคารของเช็ค)</small>
    </label>
    <input type="text" id="bank_name" name="bank_name" class="form-control" maxlength="100"
           value="{{ $bankValue }}" placeholder="เช่น กสิกรไทย">

    @if ($companyBank)
        <div class="form-text" data-bank-hint @if ($method !== 'transfer') hidden @endif>
            <i class="bi bi-bank me-1"></i>บัญชีรับเงินของบริษัท: {{ $companyBank }}
            @if ($accountNo) · เลขที่ {{ $accountNo }} @endif
            @if ($accountName) · {{ $accountName }} @endif
        </div>
    @else
        <div class="form-text" data-bank-hint @if ($method !== 'transfer') hidden @endif>
            ยังไม่ได้ตั้งบัญชีรับเงิน — ตั้งได้ที่ ตั้งค่า &gt; การรับชำระเงิน
        </div>
    @endif
</div>

<script>
    (() => {
        const box    = document.currentScript.previousElementSibling;   // [data-bank-box]
        const form   = box.closest('form');
        const method = form.querySelector('#method');
        const input  = box.querySelector('#bank_name');
        const bank   = @json($companyBank);

        function sync() {
            const m = method.value;
            box.hidden = m === 'cash';
            box.querySelector('[data-bank-label-transfer]').hidden = m !== 'transfer';
            box.querySelector('[data-bank-label-cheque]').hidden   = m !== 'cheque';
            box.querySelector('[data-bank-hint]').hidden           = m !== 'transfer';

            if (m === 'transfer') {
                if (!input.value.trim() && bank) input.value = bank;   // ไม่ทับค่าที่ผู้ใช้พิมพ์เอง
            } else if (input.value.trim() === bank) {
                input.value = '';                                       // เช็ค / เงินสด ไม่ใช้ธนาคารบริษัท
            }
        }

        method.addEventListener('change', sync);
    })();
</script>
