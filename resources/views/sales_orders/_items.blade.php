{{--
    ตารางรายการสินค้า + คำนวณยอดฝั่ง client
    ต้องส่งเข้ามา: $products, $rows (collection ของ ['id_product','quantity','price'])
--}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-4">
        <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">รายการสินค้า</h6>

        <div class="table-responsive">
            <table class="table align-middle" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:40%">สินค้า</th>
                        <th style="width:18%" class="text-end">จำนวน</th>
                        <th style="width:18%" class="text-end">ราคา/หน่วย</th>
                        <th style="width:18%" class="text-end">รวม</th>
                        <th style="width:6%"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>

        <button type="button" class="btn btn-outline-dark btn-sm" id="addRow">
            <i class="bi bi-plus-lg me-1"></i> เพิ่มรายการ
        </button>

        <div class="row justify-content-end mt-4">
            <div class="col-md-5">
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">รวมเป็นเงิน</span>
                    <span id="sumSubtotal">0.00</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span class="text-muted">ส่วนลด</span>
                    <input type="number" step="0.01" min="0" name="discount" id="discount"
                           value="{{ old('discount', $discountValue ?? 0) }}"
                           class="form-control form-control-sm text-end" style="width:140px">
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">ภาษีมูลค่าเพิ่ม 7%</span>
                    <span id="sumVat">0.00</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-top fw-bold fs-5">
                    <span>ยอดสุทธิ</span>
                    <span id="sumTotal">0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="items[__INDEX__][id_product]" class="form-select product-select" required>
                <option value="">— เลือกสินค้า —</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id_product }}"
                            data-price="{{ $product->price_per_unit ?? $product->price ?? 0 }}">
                        {{ $product->name_product }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01"
                   name="items[__INDEX__][quantity]"
                   class="form-control text-end qty-input" required>
        </td>
        <td>
            <input type="number" step="0.01" min="0"
                   name="items[__INDEX__][price]"
                   class="form-control text-end price-input" required>
        </td>
        <td class="text-end line-total">0.00</td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
(() => {
    const VAT_RATE = 0.07;

    const body        = document.getElementById('itemsBody');
    const template    = document.getElementById('rowTemplate');
    const addButton   = document.getElementById('addRow');
    const discountInp = document.getElementById('discount');

    let index = 0;

    const money = n => Number(n || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });

    function recalc() {
        let subtotal = 0;

        body.querySelectorAll('tr').forEach(row => {
            const qty   = parseFloat(row.querySelector('.qty-input').value)   || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const line  = Math.round(qty * price * 100) / 100;

            row.querySelector('.line-total').textContent = money(line);
            subtotal += line;
        });

        subtotal = Math.round(subtotal * 100) / 100;

        let discount = parseFloat(discountInp.value) || 0;
        if (discount > subtotal) discount = subtotal;

        const afterDiscount = Math.round((subtotal - discount) * 100) / 100;
        const vat           = Math.round(afterDiscount * VAT_RATE * 100) / 100;

        document.getElementById('sumSubtotal').textContent = money(subtotal);
        document.getElementById('sumVat').textContent      = money(vat);
        document.getElementById('sumTotal').textContent    = money(afterDiscount + vat);
    }

    function addRow(data = {}) {
        const html = template.innerHTML.replaceAll('__INDEX__', index++);
        const temp = document.createElement('tbody');
        temp.innerHTML = html.trim();
        const row = temp.firstElementChild;

        if (data.id_product) row.querySelector('.product-select').value = data.id_product;
        if (data.quantity)   row.querySelector('.qty-input').value      = data.quantity;
        if (data.price)      row.querySelector('.price-input').value    = data.price;

        body.appendChild(row);
        recalc();
    }

    body.addEventListener('input', recalc);
    discountInp.addEventListener('input', recalc);

    // เลือกสินค้าแล้วเติมราคาให้ ถ้ายังไม่ได้กรอกเอง
    body.addEventListener('change', e => {
        if (!e.target.classList.contains('product-select')) return;

        const priceInput = e.target.closest('tr').querySelector('.price-input');
        const suggested  = e.target.selectedOptions[0]?.dataset?.price;

        if (suggested && !priceInput.value) {
            priceInput.value = suggested;
            recalc();
        }
    });

    body.addEventListener('click', e => {
        if (!e.target.closest('.remove-row')) return;

        e.target.closest('tr').remove();
        if (!body.querySelector('tr')) addRow();
        recalc();
    });

    addButton.addEventListener('click', () => addRow());

    // เติมแถวเริ่มต้น
    const initialRows = @json($rows ?? []);

    if (initialRows.length) {
        initialRows.forEach(row => addRow(row));
    } else {
        addRow();
    }
})();
</script>