{{--
    หน้าต่างออกใบเสร็จขายสด (ลูกค้าซื้อแล้วจ่ายทันที ไม่ต้องมีแคมป์ / ใบส่งของ / ใบแจ้งหนี้)
    ใบเสร็จตามใบแจ้งหนี้ ออกจากปุ่มในหน้าใบแจ้งหนี้

    ต้องส่ง: $customers, $camps, $products, $walkIn, $requireSlip
    กรอกผิดแล้วเด้งกลับ (error bag createReceipt) → หน้าต่างเปิดเองพร้อมค่าที่กรอกไว้
    ปุ่ม "ลูกค้าใหม่" → หน้าต่างเพิ่มลูกค้า (customers._quick-modal) ไม่ต้องย้ายหน้า
--}}
@php
    $vatRate = \App\Models\Invoice::VAT_RATE;

    // ข้อมูลสินค้าไว้เติมราคาอัตโนมัติ (ฝังเป็น JSON ไม่ต้องยิง AJAX)
    $productMap = $products->mapWithKeys(fn ($p) => [
        $p->id_product => ['price' => (float) ($p->unit_price ?? 0)],
    ]);

    // แคมป์ของลูกค้าแต่ละราย ไว้กรอง dropdown แคมป์
    $campMap = $camps->map(fn ($c) => [
        'id'       => $c->id_camp,
        'customer' => $c->id_customer,
        'label'    => $c->code_camp . ' — ' . $c->name_camp,
    ])->values();

    $oldItems = old('items', [['id_product' => '', 'quantity' => '', 'price' => '']]);
@endphp

<div class="modal fade" id="createReceiptModal" tabindex="-1" aria-labelledby="createReceiptTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('receipts.store') }}" enctype="multipart/form-data"
              data-confirm="ต้องการออกใบเสร็จนี้หรือไม่ ตรวจสอบยอดเงินและหลักฐานให้ถูกต้องก่อนยืนยัน"
              data-confirm-title="ยืนยันการออกใบเสร็จ" data-confirm-ok="ออกใบเสร็จ" data-confirm-variant="success">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title" id="createReceiptTitle"><i class="bi bi-cart-check me-1"></i>ออกใบเสร็จขายสด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-light border small py-2">
                    ลูกค้าซื้อแล้วจ่ายทันที — ไม่ต้องมีแคมป์ ใบส่งของ หรือใบแจ้งหนี้
                    · ถ้าเป็นของงานที่มีแคมป์อยู่แล้ว เลือกแคมป์ได้ (ไม่บังคับ) เพื่อให้ยอดไปรวมในสรุปแคมป์
                </div>

                @include('partials.modal-errors', ['bag' => $errors->createReceipt])

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

                            {{-- ---------- ขายสด ---------- --}}
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="id_customer" class="form-label">ลูกค้า <span class="text-danger">*</span></label>
                                    <select id="id_customer" name="id_customer" class="form-select" required
                                            data-customer-select data-searchable data-placeholder="พิมพ์ชื่อลูกค้า..."
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
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-quick-customer>
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

                            {{-- ยอดที่ต้องรับ: คิดจากรายการสินค้า + VAT --}}
                            <div class="mb-3">
                                <label for="payTotal" class="form-label">ยอดรับชำระ</label>
                                <div class="input-group">
                                    <input type="text" id="payTotal" class="form-control fw-bold text-end bg-light" value="0.00"
                                           readonly tabindex="-1">
                                    <span class="input-group-text">บาท</span>
                                </div>
                                <div class="form-text">คำนวณจากรายการสินค้า + VAT {{ $vatRate }}% ให้อัตโนมัติ</div>
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

                            @include('partials.bank-input', ['method' => old('method', 'transfer')])

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
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-dark"><i class="bi bi-receipt me-1"></i>ออกใบเสร็จ</button>
            </div>
        </form>
    </div>
</div>

{{-- ปุ่ม "ลูกค้าใหม่" เปิดหน้าต่างเพิ่มลูกค้า แล้วเลือกลูกค้าที่เพิ่งเพิ่มให้เลย --}}
@include('customers._quick-modal', ['action' => route('receipts.customers.store')])

<script>
(() => {
    const vatRate = {{ $vatRate }};
    const fmt = (n) => n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const round2 = (n) => Math.round(n * 100) / 100;

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

    /* ---------- ลูกค้าทั่วไป (ลูกค้าใหม่ → customers._quick-modal) ---------- */
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

    recalc();
})();

/* ---------- ยอดรับชำระ + เงินทอน ---------- */
(() => {
    // ใช้ฟอร์มของช่องวิธีชำระตรง ๆ — ห้ามหาด้วย action$="/receipts" เพราะจะไปเจอฟอร์มค้นหาของหน้ารายการก่อน
    const method   = document.getElementById('method');
    const form     = method.form;
    const payTotal = document.getElementById('payTotal');
    const cashBox  = document.querySelector('[data-cash-box]');
    const received = document.getElementById('cash_received');
    const change   = document.getElementById('cashChange');
    const fmt      = (n) => n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // ยอดปัจจุบัน = ยอดรวมที่ตารางคำนวณไว้
    function currentTotal() {
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
