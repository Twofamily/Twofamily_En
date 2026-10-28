@extends('layout')

{{--
    ออกใบแจ้งหนี้ / ใบวางบิล จากใบส่งของที่ยังไม่ได้เรียกเก็บ
    ขั้นที่ 1 เลือกแคมป์ (หรือลูกค้าที่ซื้อโดยไม่ผ่านแคมป์)
    ขั้นที่ 2 ติ๊กใบส่งของที่จะรวมในบิลนี้ → ระบบรวมยอด + VAT ให้
--}}

@section('namepage')
    <div class="container">
        <h3 class="mb-0">ออกใบแจ้งหนี้ / ใบวางบิล</h3>
    </div>
@endsection

@php
    // ค่าที่ติ๊กไว้: กรอกผิดแล้วเด้งกลับ → ตามที่เลือกไว้ / มาจากหน้าใบส่งของ → ใบนั้น / อื่น ๆ → ทั้งหมด
    $checked = collect(old('delivery_notes', $preselect ? [$preselect] : $notes->pluck('id_delivery_note')->all()))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

@section('content')
    <style>
        .inv-create .note-row { cursor: pointer; }
        .inv-create .note-row.table-active td { background-color: rgba(25, 135, 84, .06); }
        .inv-create .summary-card { position: sticky; top: 1rem; }
        .inv-create .summary-card .total { font-size: 1.6rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    </style>

    <div class="container py-3 inv-create">

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- ===== ขั้นที่ 1 เลือกแคมป์ / ลูกค้า ===== --}}
        <form method="GET" action="{{ route('invoices.create') }}" class="mb-4">
            <label for="group_picker" class="form-label fw-semibold">1. เลือกแคมป์ หรือลูกค้า</label>
            <select id="group_picker" name="group" class="form-select" onchange="this.form.submit()">
                <option value="">— เลือกแคมป์ / ลูกค้า ที่มีใบส่งของรอออกใบแจ้งหนี้ —</option>
                @foreach ($groups as $key => $g)
                    <option value="{{ $key }}" @selected($group === $key)>
                        {{ $g['label'] }} — รอออก {{ $g['count'] }} ใบ · {{ number_format($g['amount'], 2) }} บาท
                    </option>
                @endforeach
            </select>
            <div class="form-text">
                ใบแจ้งหนี้ 1 ใบ รวมใบส่งของได้หลายใบ แต่ต้องเป็นลูกค้าและแคมป์เดียวกัน
            </div>
        </form>

        @if ($groups->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-check2-all fs-1 d-block mb-2"></i>
                ไม่มีใบส่งของที่รอออกใบแจ้งหนี้
                <div class="mt-3">
                    <a href="{{ route('delivery_notes.index') }}" class="btn btn-outline-secondary btn-sm">ไปหน้าใบส่งของ</a>
                </div>
            </div>
        @elseif ($group)
            <form method="POST" action="{{ route('invoices.store') }}" id="invoiceForm"
                  data-confirm="ระบบจะสร้างใบแจ้งหนี้จากใบส่งของที่เลือก ใบส่งของเหล่านี้จะแก้ไขหรือลบไม่ได้อีก"
                  data-confirm-title="ยืนยันการออกใบแจ้งหนี้"
                  data-confirm-variant="success"
                  data-confirm-ok="ออกใบแจ้งหนี้">
                @csrf

                <div class="row g-4">
                    {{-- ===== ขั้นที่ 2 เลือกใบส่งของ ===== --}}
                    <div class="col-lg-8">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <label class="form-label fw-semibold mb-0">2. เลือกใบส่งของที่จะเรียกเก็บ</label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" data-check="all">เลือกทั้งหมด</button>
                                <button type="button" class="btn btn-outline-secondary" data-check="none">ไม่เลือก</button>
                            </div>
                        </div>

                        @error('delivery_notes')
                            <div class="alert alert-danger py-2 small">{{ $message }}</div>
                        @enderror

                        <div class="table-responsive shadow-sm rounded-3">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:44px;"></th>
                                        <th>ใบส่งของ</th>
                                        <th>วันที่ส่ง</th>
                                        <th>รายการ</th>
                                        <th class="text-end">มูลค่า (ก่อน VAT)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $n)
                                        @php $isChecked = in_array($n->id_delivery_note, $checked, true); @endphp
                                        <tr class="note-row {{ $isChecked ? 'table-active' : '' }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input note-check"
                                                       name="delivery_notes[]" value="{{ $n->id_delivery_note }}"
                                                       id="dn{{ $n->id_delivery_note }}"
                                                       data-amount="{{ $n->subtotal() }}"
                                                       data-date="{{ $n->delivery_date?->format('Y-m-d') }}"
                                                       @checked($isChecked)>
                                            </td>
                                            <td>
                                                <label for="dn{{ $n->id_delivery_note }}" class="fw-semibold mb-0">
                                                    {{ $n->code_delivery }}
                                                </label>
                                                <a href="{{ route('delivery_notes.show', $n) }}" target="_blank"
                                                   class="small ms-1 text-decoration-none" title="เปิดใบส่งของ">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            </td>
                                            <td>{{ $n->delivery_date?->format('d/m/Y') }}</td>
                                            <td class="small text-muted">
                                                @foreach ($n->details as $d)
                                                    <div>{{ $d->product->name_product ?? '-' }} {{ number_format($d->quantity, 2) }} × {{ number_format($d->price_per_unit, 2) }}</div>
                                                @endforeach
                                            </td>
                                            <td class="text-end">{{ number_format($n->subtotal(), 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ===== ขั้นที่ 3 วันที่ / เครดิต / สรุปยอด ===== --}}
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm summary-card">
                            <div class="card-body p-4">
                                <div class="fw-semibold mb-3">3. รายละเอียดบิล</div>

                                <div class="mb-3">
                                    <label for="invoice_date" class="form-label">วันที่ออกใบแจ้งหนี้</label>
                                    <input type="date" id="invoice_date" name="invoice_date"
                                           class="form-control @error('invoice_date') is-invalid @enderror"
                                           value="{{ old('invoice_date', now()->format('Y-m-d')) }}"
                                           max="{{ now()->format('Y-m-d') }}"
                                           required data-required-msg="กรุณาเลือกวันที่ออกใบแจ้งหนี้">
                                    @error('invoice_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text" id="dateHint">ย้อนได้ถึงวันส่งของใบล่าสุดที่เลือก</div>
                                </div>

                                <div class="mb-3">
                                    <label for="credit_days" class="form-label">เครดิตเทอม</label>
                                    <div class="input-group">
                                        <input type="number" id="credit_days" name="credit_days" min="0" max="180" step="1"
                                               class="form-control @error('credit_days') is-invalid @enderror"
                                               value="{{ old('credit_days', $creditDays) }}" required>
                                        <span class="input-group-text">วัน</span>
                                    </div>
                                    @error('credit_days')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">ครบกำหนดชำระ <strong id="dueDate">-</strong></div>
                                </div>

                                <hr>

                                <dl class="row small mb-2">
                                    <dt class="col-7 fw-normal text-muted">ใบส่งของที่เลือก</dt>
                                    <dd class="col-5 text-end mb-1" id="sumCount">0 ใบ</dd>
                                    <dt class="col-7 fw-normal text-muted">รวมเป็นเงิน</dt>
                                    <dd class="col-5 text-end mb-1" id="sumSubtotal">0.00</dd>
                                    <dt class="col-7 fw-normal text-muted">ภาษีมูลค่าเพิ่ม {{ \App\Models\Invoice::VAT_RATE }}%</dt>
                                    <dd class="col-5 text-end mb-1" id="sumVat">0.00</dd>
                                </dl>
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <span class="text-muted">ยอดสุทธิ</span>
                                    <span class="total" id="sumTotal">0.00</span>
                                </div>

                                <button type="submit" class="btn btn-dark w-100 mt-4" id="submitBtn">
                                    <i class="bi bi-receipt me-1"></i>ออกใบแจ้งหนี้
                                </button>
                                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary w-100 mt-2">ยกเลิก</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const VAT = {{ \App\Models\Invoice::VAT_RATE }};
                    const checks = Array.from(document.querySelectorAll('.note-check'));
                    const dateEl = document.getElementById('invoice_date');
                    const creditEl = document.getElementById('credit_days');
                    const fmt = (v) => v.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const thai = (iso) => iso ? iso.split('-').reverse().join('/') : '-';

                    function refresh() {
                        const picked = checks.filter((c) => c.checked);
                        const subtotal = Math.round(picked.reduce((s, c) => s + parseFloat(c.dataset.amount || 0), 0) * 100) / 100;
                        const vat = Math.round(subtotal * VAT) / 100;

                        document.getElementById('sumCount').textContent = picked.length + ' ใบ';
                        document.getElementById('sumSubtotal').textContent = fmt(subtotal);
                        document.getElementById('sumVat').textContent = fmt(vat);
                        document.getElementById('sumTotal').textContent = fmt(subtotal + vat);
                        document.getElementById('submitBtn').disabled = picked.length === 0;

                        checks.forEach((c) => c.closest('tr').classList.toggle('table-active', c.checked));

                        // วันที่ออกต้องไม่ก่อนวันส่งของใบล่าสุดที่เลือก
                        const latest = picked.map((c) => c.dataset.date).sort().pop() || '';
                        dateEl.min = latest;
                        document.getElementById('dateHint').textContent = latest
                            ? 'ย้อนได้ถึง ' + thai(latest) + ' (วันส่งของใบล่าสุดที่เลือก)'
                            : 'ย้อนได้ถึงวันส่งของใบล่าสุดที่เลือก';

                        // ครบกำหนด = วันที่ออก + เครดิต
                        const d = dateEl.value ? new Date(dateEl.value + 'T00:00:00') : null;
                        const days = parseInt(creditEl.value, 10);
                        if (d && !isNaN(days)) {
                            d.setDate(d.getDate() + days);
                            const iso = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                            document.getElementById('dueDate').textContent = thai(iso);
                        }
                    }

                    // กดที่แถวก็ติ๊กได้ (ไม่ต้องเล็งช่องเล็ก ๆ)
                    document.querySelectorAll('.note-row').forEach((row) => {
                        row.addEventListener('click', (e) => {
                            if (e.target.closest('a, input, label')) return;
                            const c = row.querySelector('.note-check');
                            c.checked = !c.checked;
                            refresh();
                        });
                    });

                    document.querySelectorAll('[data-check]').forEach((b) => b.addEventListener('click', () => {
                        checks.forEach((c) => (c.checked = b.dataset.check === 'all'));
                        refresh();
                    }));

                    checks.forEach((c) => c.addEventListener('change', refresh));
                    dateEl.addEventListener('change', refresh);
                    creditEl.addEventListener('input', refresh);
                    refresh();
                });
            </script>
        @endif
    </div>
@endsection
