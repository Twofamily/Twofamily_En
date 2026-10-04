{{-- แท็บเอกสาร --}}

{{-- ==================== รูปแบบเลขที่เอกสาร ==================== --}}
<h6 class="fw-semibold">รูปแบบเลขที่เอกสาร</h6>

<div class="row g-3 mt-1">
    <div class="col-md-4">@include('settings._field', ['key' => 'doc.year_format'])</div>
    <div class="col-md-4">@include('settings._field', ['key' => 'doc.reset_period'])</div>
    <div class="col-md-4">@include('settings._field', ['key' => 'doc.running_digits'])</div>
</div>

<div class="alert alert-light border mt-3 mb-3">
    <i class="bi bi-eye me-2"></i>ตัวอย่างใบเสนอราคาใบแรกของรอบ:
    <strong class="font-monospace" id="codePreview">-</strong>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>ประเภทเอกสาร</th>
                <th style="width: 180px;">ตัวย่อ</th>
                <th>เลขถัดไป <span class="fw-normal text-muted small">(ตามค่าที่บันทึกแล้ว)</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documentTypes as $type => $info)
                @php $key = "doc.prefix.{$type}"; @endphp
                <tr>
                    <td>{{ $info['label'] }}</td>
                    <td>
                        <input type="text" name="doc[prefix][{{ $type }}]" maxlength="10"
                            value="{{ old($key, $values[$key]) }}" data-prefix="{{ $type }}"
                            class="form-control form-control-sm text-uppercase @error($key) is-invalid @enderror">
                        @error($key)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>
                    <td><code>{{ $nextNumbers[$type] ?? '-' }}</code></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="form-text">
    ตัวย่อใช้ได้เฉพาะ A-Z และ 0-9 ห้ามซ้ำกัน
    การเปลี่ยนรูปแบบมีผลกับเอกสารใหม่เท่านั้น เลขของเอกสารเดิมจะไม่เปลี่ยน
</div>

<hr class="my-4">

{{-- ==================== ภาษีและเงื่อนไข ==================== --}}
<h6 class="fw-semibold">ภาษีและเงื่อนไข</h6>

<div class="row g-3 mt-1">
    <div class="col-md-4">
        @include('settings._field', [
            'key'  => 'doc.vat_rate',
            'hint' => 'มีผลกับเอกสารที่สร้างใหม่เท่านั้น เอกสารเดิมใช้อัตราที่บันทึกไว้ในใบ',
        ])
    </div>
    <div class="col-md-4">
        @include('settings._field', [
            'key'  => 'doc.quotation_valid_days',
            'hint' => 'ใช้คำนวณวันหมดอายุของใบเสนอราคา',
        ])
    </div>
    <div class="col-md-4">
        @include('settings._field', [
            'key'  => 'doc.credit_days',
            'hint' => 'ใช้คำนวณวันครบกำหนดชำระในใบแจ้งหนี้',
        ])
    </div>
    <div class="col-12">@include('settings._field', ['key' => 'doc.show_signature'])</div>
</div>

<hr class="my-4">

{{-- ==================== หมายเหตุท้ายเอกสาร ==================== --}}
<h6 class="fw-semibold">หมายเหตุท้ายเอกสาร</h6>

<div class="row g-3 mt-1">
    @foreach ($documentTypes as $type => $info)
        <div class="col-md-6">@include('settings._field', ['key' => "doc.note.{$type}"])</div>
    @endforeach
</div>

{{-- ตัวอย่างเลขแบบสด: เปลี่ยนค่าในฟอร์มแล้วเห็นผลทันที ก่อนกดบันทึก --}}
<script>
    (function() {
        var form   = document.currentScript.closest('form');
        var year   = form.querySelector('[name="doc[year_format]"]');
        var reset  = form.querySelector('[name="doc[reset_period]"]');
        var digits = form.querySelector('[name="doc[running_digits]"]');
        var prefix = form.querySelector('[data-prefix="quotation"]');
        var output = document.getElementById('codePreview');

        function render() {
            var now = new Date();
            var y   = year.value === 'be' ? now.getFullYear() + 543 : now.getFullYear();
            var m   = String(now.getMonth() + 1).padStart(2, '0');

            var period = reset.value === 'monthly' ? y + '-' + m
                       : reset.value === 'never'   ? ''
                       : String(y);

            var size    = Math.min(Math.max(parseInt(digits.value, 10) || 4, 3), 6);
            var running = '1'.padStart(size, '0');
            var p       = (prefix.value || 'QT').trim().toUpperCase();

            output.textContent = [p, period, running].filter(Boolean).join('-');
        }

        [year, reset, digits, prefix].forEach(function(el) {
            el.addEventListener('input', render);
            el.addEventListener('change', render);
        });

        render();
    })();
</script>