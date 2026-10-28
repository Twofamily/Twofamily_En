@extends('layout')

{{--
    ออกใบเสร็จโดยไม่ต้องมีใบแจ้งหนี้
      ?type=advance : รับเงินล่วงหน้า ผูกแคมป์ (ลูกค้าจ่ายก่อนส่งของ)
      ?type=direct  : ขายสด (ลูกค้าซื้อแล้วจ่ายทันที ไม่ต้องมีแคมป์)
    ใบเสร็จตามใบแจ้งหนี้ ออกจากปุ่มในหน้าใบแจ้งหนี้เหมือนเดิม
--}}

@php
    $isAdvance = $type === 'advance';
    $vatRate   = \App\Models\Invoice::VAT_RATE;

    // ข้อมูลสินค้าไว้เติมราคาอัตโนมัติ (ฝังเป็น JSON ไม่ต้องยิง AJAX)
    $productMap = $products->mapWithKeys(fn ($p) => [
        $p->id_product => ['price' => (float) ($p->unit_price ?? 0)],
    ]);

    // แคมป์ของลูกค้าแต่ละราย ไว้กรอง dropdown แคมป์ในโหมดขายสด
    $campMap = $camps->map(fn ($c) => [
        'id'       => $c->id_camp,
        'customer' => $c->id_customer,
        'label'    => $c->code_camp . ' — ' . $c->name_camp,
    ])->values();

    $oldItems = old('items', [['id_product' => '', 'quantity' => '', 'price' => '']]);
@endphp

@section('namepage')
    <div class="container">
        <h3>{{ $isAdvance ? 'ออกใบเสร็จรับเงินล่วงหน้า' : 'ออกใบเสร็จขายสด' }}</h3>
    </div>
@endsection

@section('content')
<div class="container py-3">

    {{-- สลับประเภท --}}
    <div class="btn-group mb-3" role="group" aria-label="ประเภทใบเสร็จ">
        <a href="{{ route('receipts.create', ['type' => 'advance', 'camp' => $selectedCamp]) }}"
           class="btn {{ $isAdvance ? 'btn-dark' : 'btn-outline-dark' }}">
            <i class="bi bi-cash-coin me-1"></i>รับเงินล่วงหน้า
        </a>
        <a href="{{ route('receipts.create', ['type' => 'direct']) }}"
           class="btn {{ $isAdvance ? 'btn-outline-dark' : 'btn-dark' }}">
            <i class="bi bi-cart-check me-1"></i>ขายสด
        </a>
    </div>

    <div class="alert alert-light border small">
        @if ($isAdvance)
            ลูกค้าจ่ายเงินก่อนส่งของ — ออกใบเสร็จผูกกับแคมป์ได้ทันที ไม่ต้องรอใบส่งของหรือใบแจ้งหนี้
            เมื่อออกใบแจ้งหนี้ของแคมป์นี้ภายหลัง กด <strong>"หักเงินรับล่วงหน้า"</strong> ในหน้าใบแจ้งหนี้ ลูกค้าจะไม่ถูกเรียกเก็บซ้ำ
        @else
            ลูกค้าซื้อแล้วจ่ายทันที — ไม่ต้องมีแคมป์ ใบส่งของ หรือใบแจ้งหนี้
            ถ้าเป็นการซื้อของงานที่มีแคมป์อยู่แล้ว เลือกแคมป์ได้ (ไม่บังคับ) เพื่อให้ยอดไปรวมในสรุปแคมป์
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('receipts.store') }}" enctype="multipart/form-data"
          data-confirm="ต้องการออกใบเสร็จนี้หรือไม่ ตรวจสอบยอดเงินและหลักฐานให้ถูกต้องก่อนยืนยัน"
          data-confirm-title="ยืนยันการออกใบเสร็จ"
          data-confirm-ok="ออกใบเสร็จ"
          data-confirm-variant="success">
        @csrf
        <input type="hidden" name="receipt_type" value="{{ $type }}">

        <div class="row g-4">
            {{-- ==================== ซ้าย: รายละเอียดใบเสร็จ ==================== --}}
            <div class="col-lg-7">
                <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">รายละเอียด</h6>

                <div class="mb-3">
                    <label for="date_receipt" class="form-label">วันที่รับเงิน <span class="text-danger">*</span></label>
                    <input type="date" id="date_receipt" name="date_receipt" class="form-control"
                           value="{{ old('date_receipt', now()->format('Y-m-d')) }}"
                           max="{{ now()->format('Y-m-d') }}" required>
                </div>

                @if ($isAdvance)
                    {{-- ---------- รับเงินล่วงหน้า ---------- --}}
                    <div class="mb-3">
                        <label for="id_camp" class="form-label">แคมป์งาน <span class="text-danger">*</span></label>
                        <select id="id_camp" name="id_camp" class="form-select" required
                                data-searchable data-placeholder="พิมพ์ชื่อหรือรหัสแคมป์...">
                            <option value="">— เลือกแคมป์ —</option>
                            @foreach ($camps as $camp)
                                <option value="{{ $camp->id_camp }}"
                                    @selected(old('id_camp', $selectedCamp) == $camp->id_camp)>
                                    {{ $camp->code_camp }} — {{ $camp->name_camp }}
                                    ({{ $camp->customer->name_customer ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">ลูกค้าบนใบเสร็จ = ลูกค้าของแคมป์ แสดงเฉพาะแคมป์ที่เปิดอยู่</div>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">ยอดเงินที่รับ (รวม VAT) <span class="text-danger">*</span></label>
                        <input type="number" id="amount" name="amount" class="form-control"
                               step="0.01" min="0.01" value="{{ old('amount') }}" required>
                        <div class="form-text" id="vatSplit">ระบบจะแยกยอดก่อน VAT และ VAT {{ $vatRate }}% ให้อัตโนมัติ</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">รายละเอียดเพิ่มเติม</label>
                        <input type="text" id="description" name="description" class="form-control" maxlength="255"
                               value="{{ old('description') }}" placeholder="เช่น มัดจำค่าดินถม 30%">
                    </div>
                @else
                    {{-- ---------- ขายสด ---------- --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="id_customer" class="form-label">ลูกค้า <span class="text-danger">*</span></label>
                            <select id="id_customer" name="id_customer" class="form-select" required
                                    data-searchable data-placeholder="พิมพ์ชื่อลูกค้า..."
                                    data-required-msg="กรุณาเลือกลูกค้า หรือกด ลูกค้าใหม่ / ลูกค้าทั่วไป">
                                <option value="">— เลือกลูกค้า —</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id_customer }}"
                                        @selected(old('id_customer') == $customer->id_customer)>
                                        {{ $customer->name_customer }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleNewCustomer">
                                    <i class="bi bi-person-plus me-1"></i>ลูกค้าใหม่
                                </button>
                                @if ($walkIn)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="pickWalkIn"
                                            data-id="{{ $walkIn->id_customer }}"
                                            data-name="{{ $walkIn->name_customer }}"
                                            title="ลูกค้าไม่แจ้งชื่อ / ไม่ต้องการใบกำกับภาษีเต็มรูป">
                                        <i class="bi bi-person me-1"></i>ลูกค้าทั่วไป (ไม่ระบุชื่อ)
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="id_camp_direct" class="form-label">
                                แคมป์ <span class="text-muted fw-normal">(ไม่บังคับ)</span>
                            </label>
                            <select id="id_camp_direct" name="id_camp" class="form-select">
                                <option value="">— ไม่ผูกแคมป์ —</option>
                            </select>
                        </div>
                    </div>

                    {{-- เพิ่มลูกค้าใหม่แบบย่อ (บันทึกผ่าน customers.store.ajax ตัวเดียวกับหน้าใบเสนอราคา)
                         ช่องในนี้ไม่มี name จึงไม่ถูกส่งไปพร้อมฟอร์มใบเสร็จ --}}
                    <div id="newCustomerBox" class="border rounded-3 p-3 mt-3 bg-light" hidden>
                        <h6 class="mb-3">เพิ่มลูกค้าใหม่</h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="nc_name" class="form-label">ชื่อลูกค้า <span class="text-danger">*</span></label>
                                <input type="text" id="nc_name" class="form-control" maxlength="255"
                                       placeholder="ชื่อ-สกุล หรือชื่อบริษัท">
                            </div>
                            <div class="col-md-4">
                                <label for="nc_type" class="form-label">ประเภท</label>
                                <select id="nc_type" class="form-select">
                                    <option value="person">บุคคล</option>
                                    <option value="company">บริษัท</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="nc_phone" class="form-label">เบอร์โทร</label>
                                <input type="text" id="nc_phone" class="form-control" maxlength="10"
                                       inputmode="numeric" placeholder="0812345678">
                            </div>
                            <div class="col-md-8">
                                <label for="nc_address" class="form-label">
                                    ที่อยู่ <span class="text-muted fw-normal">(ไม่บังคับ — ใส่ถ้าลูกค้าต้องการใบกำกับภาษีเต็มรูป)</span>
                                </label>
                                <input type="text" id="nc_address" class="form-control" maxlength="255"
                                       placeholder="บ้านเลขที่ ถนน ตำบล อำเภอ จังหวัด">
                            </div>
                        </div>

                        <div id="nc_error" class="alert alert-danger mt-3 mb-0 py-2 small" hidden></div>

                        <div class="mt-3 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="nc_cancel">ยกเลิก</button>
                            <button type="button" class="btn btn-sm btn-dark" id="nc_save">บันทึกลูกค้า</button>
                        </div>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table align-middle" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>สินค้า</th>
                                    <th style="width: 120px;">จำนวน</th>
                                    <th style="width: 140px;">ราคา/หน่วย</th>
                                    <th class="text-end" style="width: 130px;">รวม</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($oldItems as $i => $item)
                                    <tr class="item-row">
                                        <td>
                                            <select name="items[{{ $i }}][id_product]" class="form-select item-product" required>
                                                <option value="">— เลือกสินค้า —</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id_product }}"
                                                        @selected(($item['id_product'] ?? '') == $product->id_product)>
                                                        {{ $product->name_product }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $i }}][quantity]" class="form-control item-qty"
                                                   step="0.01" min="0.01" value="{{ $item['quantity'] ?? '' }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $i }}][price]" class="form-control item-price"
                                                   step="0.01" min="0" value="{{ $item['price'] ?? '' }}" required>
                                        </td>
                                        <td class="text-end item-total">0.00</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="ลบแถว">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addRow">
                                            <i class="bi bi-plus-lg me-1"></i>เพิ่มรายการ
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end fw-normal text-muted">รวมเป็นเงิน</th>
                                    <th class="text-end" id="sumSubtotal">0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end fw-normal text-muted">ภาษีมูลค่าเพิ่ม {{ $vatRate }}%</th>
                                    <th class="text-end" id="sumVat">0.00</th>
                                    <th></th>
                                </tr>
                                <tr class="table-primary fw-bold">
                                    <th colspan="3" class="text-end">ยอดรับชำระ</th>
                                    <th class="text-end" id="sumTotal">0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                <div class="mb-3">
                    <label for="note" class="form-label">หมายเหตุ</label>
                    <textarea id="note" name="note" class="form-control" rows="2" maxlength="1000">{{ old('note') }}</textarea>
                </div>
            </div>

            {{-- ==================== ขวา: หลักฐานการชำระ ==================== --}}
            <div class="col-lg-5">
                <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">หลักฐานการชำระเงิน</h6>

                <div class="mb-3">
                    <label for="method" class="form-label">วิธีชำระ <span class="text-danger">*</span></label>
                    <select id="method" name="method" class="form-select" required>
                        @foreach (\App\Models\Payment::METHODS as $key => $label)
                            <option value="{{ $key }}" @selected(old('method', 'transfer') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ยอดที่ต้องรับ: ขายสดคิดจากรายการสินค้า + VAT / รับล่วงหน้าคือยอดที่กรอกด้านซ้าย --}}
                <div class="mb-3">
                    <label for="payTotal" class="form-label">ยอดรับชำระ</label>
                    <div class="input-group">
                        <input type="text" id="payTotal" class="form-control fw-bold text-end bg-light" value="0.00"
                               readonly tabindex="-1">
                        <span class="input-group-text">บาท</span>
                    </div>
                    <div class="form-text">
                        {{ $isAdvance ? 'ตามยอดเงินที่รับ (ช่องด้านซ้าย)' : 'คำนวณจากรายการสินค้า + VAT ' . $vatRate . '% ให้อัตโนมัติ' }}
                    </div>
                </div>

                {{-- เงินสด: กรอกเงินที่ลูกค้ายื่นมา ระบบคิดเงินทอนให้ (ไม่บังคับ) --}}
                <div class="mb-3" data-cash-box hidden>
                    <label for="cash_received" class="form-label">
                        รับเงินมา <small class="text-muted fw-normal">(ไม่บังคับ — ไว้คิดเงินทอน)</small>
                    </label>
                    <div class="input-group">
                        <input type="number" id="cash_received" name="cash_received" class="form-control text-end"
                               step="0.01" min="0" value="{{ old('cash_received') }}">
                        <span class="input-group-text">บาท</span>
                    </div>
                    <div class="form-text fw-semibold" id="cashChange"></div>
                </div>

                <div class="mb-3" data-noncash>
                    <label for="bank_name" class="form-label">ธนาคาร <small class="text-muted">(กรณีโอนเงิน)</small></label>
                    <input type="text" id="bank_name" name="bank_name" class="form-control" maxlength="100"
                           value="{{ old('bank_name') }}" placeholder="เช่น กสิกรไทย">
                </div>

                <div class="mb-3" data-noncash>
                    <label for="reference_no" class="form-label">เลขอ้างอิง / เลขเช็ค</label>
                    <input type="text" id="reference_no" name="reference_no" class="form-control" maxlength="50"
                           value="{{ old('reference_no') }}">
                </div>

                <div class="mb-3">
                    @include('partials.slip-input', [
                        'requireSlip' => $requireSlip,
                        'method'      => old('method', 'transfer'),
                    ])
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-receipt me-1"></i>ออกใบเสร็จ
                    </button>
                    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    const vatRate = {{ $vatRate }};
    const fmt = (n) => n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const round2 = (n) => Math.round(n * 100) / 100;

    /* ---------- รับเงินล่วงหน้า: แสดงยอดก่อน VAT / VAT ---------- */
    const amountInput = document.getElementById('amount');
    if (amountInput) {
        const hint = document.getElementById('vatSplit');
        const showSplit = () => {
            const gross = parseFloat(amountInput.value) || 0;
            if (gross <= 0) return;
            const sub = round2(gross * 100 / (100 + vatRate));
            hint.textContent = 'ก่อน VAT ' + fmt(sub) + ' บาท · VAT ' + vatRate + '% ' + fmt(round2(gross - sub)) + ' บาท';
        };
        amountInput.addEventListener('input', showSplit);
        showSplit();
    }

    /* ---------- ขายสด: ตารางรายการ ---------- */
    const table = document.getElementById('itemsTable');
    if (!table) return;

    const productMap = @json($productMap);
    const campMap    = @json($campMap);
    const oldCamp    = @json(old('id_camp'));

    const tbody = table.querySelector('tbody');
    let nextIndex = tbody.querySelectorAll('.item-row').length;

    function recalc() {
        let subtotal = 0;
        tbody.querySelectorAll('.item-row').forEach((row) => {
            const qty   = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const total = round2(qty * price);
            row.querySelector('.item-total').textContent = fmt(total);
            subtotal += total;
        });
        subtotal = round2(subtotal);
        const vat = round2(subtotal * vatRate / 100);
        document.getElementById('sumSubtotal').textContent = fmt(subtotal);
        document.getElementById('sumVat').textContent = fmt(vat);
        document.getElementById('sumTotal').textContent = fmt(round2(subtotal + vat));
    }

    tbody.addEventListener('input', recalc);

    // เลือกสินค้าแล้วเติมราคาให้ (ถ้ายังไม่ได้กรอก)
    tbody.addEventListener('change', (e) => {
        if (!e.target.classList.contains('item-product')) return;
        const priceInput = e.target.closest('tr').querySelector('.item-price');
        const info = productMap[e.target.value];
        if (info && info.price > 0 && !priceInput.value) {
            priceInput.value = info.price;
        }
        recalc();
    });

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-row');
        if (!btn) return;
        if (tbody.querySelectorAll('.item-row').length <= 1) return; // ต้องเหลืออย่างน้อย 1 แถว
        btn.closest('tr').remove();
        recalc();
    });

    document.getElementById('addRow').addEventListener('click', () => {
        const template = tbody.querySelector('.item-row');
        const row = template.cloneNode(true);
        row.querySelectorAll('select, input').forEach((el) => {
            el.name = el.name.replace(/items\[\d+\]/, 'items[' + nextIndex + ']');
            el.value = '';
        });
        nextIndex++;
        tbody.appendChild(row);
        recalc();
    });

    // แคมป์: แสดงเฉพาะแคมป์ของลูกค้าที่เลือก
    const customerSelect = document.getElementById('id_customer');
    const campSelect     = document.getElementById('id_camp_direct');

    function fillCamps() {
        const current = campSelect.value || oldCamp;
        campSelect.innerHTML = '<option value="">— ไม่ผูกแคมป์ —</option>';
        campMap.filter((c) => String(c.customer) === String(customerSelect.value))
            .forEach((c) => {
                const opt = new Option(c.label, c.id, false, String(c.id) === String(current));
                campSelect.add(opt);
            });
    }

    customerSelect.addEventListener('change', fillCamps);
    fillCamps();

    /* ---------- ลูกค้าใหม่ / ลูกค้าทั่วไป ---------- */
    function pickCustomer(id, name) {
        let opt = Array.from(customerSelect.options).find((o) => o.value === String(id));
        if (!opt) {
            opt = new Option(name, id);
            customerSelect.add(opt);
        }
        customerSelect.value = String(id);
        // แจ้ง searchable-select ให้แสดงชื่อ + เคลียร์กรอบแดง + เติมแคมป์ของลูกค้า
        customerSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const walkInBtn = document.getElementById('pickWalkIn');
    if (walkInBtn) {
        walkInBtn.addEventListener('click', () => pickCustomer(walkInBtn.dataset.id, walkInBtn.dataset.name));
    }

    const box     = document.getElementById('newCustomerBox');
    const errBox  = document.getElementById('nc_error');
    const showErr = (msg) => { errBox.textContent = msg; errBox.hidden = !msg; };

    document.getElementById('toggleNewCustomer').addEventListener('click', () => {
        box.hidden = !box.hidden;
        showErr('');
        if (!box.hidden) document.getElementById('nc_name').focus();
    });
    document.getElementById('nc_cancel').addEventListener('click', () => { box.hidden = true; showErr(''); });

    document.getElementById('nc_save').addEventListener('click', async function () {
        const name = document.getElementById('nc_name').value.trim();
        if (!name) { showErr('กรุณากรอกชื่อลูกค้า'); return; }

        this.disabled = true;
        this.textContent = 'กำลังบันทึก...';
        showErr('');

        try {
            const res = await fetch(@json(route('receipts.customers.store')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({
                    name_customer:  name,
                    customer_type:  document.getElementById('nc_type').value,
                    phone_customer: document.getElementById('nc_phone').value.trim(),
                    address_detail: document.getElementById('nc_address').value.trim(),
                }),
            });

            if (res.status === 422) {
                const err = await res.json();
                showErr(Object.values(err.errors || {}).flat().join(' · '));
                return;
            }
            if (res.status === 419) { showErr('เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่'); return; }
            if (res.status === 403) { showErr('บัญชีนี้ไม่มีสิทธิ์เพิ่มลูกค้า'); return; }
            if (!res.ok) { showErr('บันทึกไม่สำเร็จ (รหัส ' + res.status + ')'); return; }

            const customer = await res.json();
            pickCustomer(customer.id_customer, customer.name_customer);

            box.hidden = true;
            ['nc_name', 'nc_phone', 'nc_address'].forEach((id) => { document.getElementById(id).value = ''; });
        } catch (e) {
            showErr('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้');
        } finally {
            this.disabled = false;
            this.textContent = 'บันทึกลูกค้า';
        }
    });
    recalc();
})();

/* ---------- ยอดรับชำระ + เงินทอน (ทั้งสองแบบ) ---------- */
(() => {
    const form     = document.querySelector('form[action$="/receipts"]') || document.getElementById('method').form;
    const method   = document.getElementById('method');
    const payTotal = document.getElementById('payTotal');
    const cashBox  = document.querySelector('[data-cash-box]');
    const received = document.getElementById('cash_received');
    const change   = document.getElementById('cashChange');
    const fmt      = (n) => n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // ยอดปัจจุบัน: รับล่วงหน้า = ช่อง amount / ขายสด = ยอดรวมที่ตารางคำนวณไว้
    function currentTotal() {
        const amount = document.getElementById('amount');
        if (amount) return Math.round((parseFloat(amount.value) || 0) * 100) / 100;
        const sum = document.getElementById('sumTotal');
        return sum ? parseFloat(sum.textContent.replace(/,/g, '')) || 0 : 0;
    }

    function update() {
        const total  = currentTotal();
        const isCash = method.value === 'cash';
        payTotal.value = fmt(total);

        cashBox.hidden = !isCash;
        document.querySelectorAll('[data-noncash]').forEach((el) => { el.hidden = isCash; });
        if (!isCash) { received.value = ''; }

        // รับเงินมาต้องไม่น้อยกว่ายอด (ถ้ากรอก)
        received.min = isCash ? total.toFixed(2) : 0;

        const got = parseFloat(received.value);
        if (!isCash || isNaN(got)) {
            change.textContent = '';
        } else if (got < total) {
            change.className = 'form-text fw-semibold text-danger';
            change.textContent = 'ยังขาดอีก ' + fmt(Math.round((total - got) * 100) / 100) + ' บาท';
        } else {
            change.className = 'form-text fw-semibold text-success';
            change.textContent = 'เงินทอน ' + fmt(Math.round((got - total) * 100) / 100) + ' บาท';
        }
    }

    // ฟังที่ระดับฟอร์ม → ทำงานหลังตารางรายการคำนวณยอดเสร็จแล้ว
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    form.addEventListener('click', () => setTimeout(update));   // เพิ่ม/ลบแถว
    update();
})();
</script>
@endsection
