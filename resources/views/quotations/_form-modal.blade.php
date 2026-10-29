{{--
    หน้าต่างสร้าง / แก้ไขใบเสนอราคา

        @include('quotations._form-modal', ['mode' => 'create'])                          // หน้ารายการ
        @include('quotations._form-modal', ['mode' => 'edit', 'quotation' => $quotation])  // หน้าดูใบเสนอราคา

    ต้องส่ง: $customers, $products
    กรอกผิดแล้วเด้งกลับ (error bag createQuotation / updateQuotation) → หน้าต่างเปิดเองพร้อมค่าที่กรอกไว้
    ปุ่ม "ลูกค้าใหม่" → หน้าต่างเพิ่มลูกค้า (customers._quick-modal) ไม่ต้องย้ายหน้า
    ราคาต่อหน่วยดึงจากหน้าสินค้า (แก้ราคาที่หน้าสินค้า) — ผู้ดูแลระบบตรวจราคาตอนอนุมัติ
--}}
@php
    $isEdit  = $mode === 'edit';
    $bag     = $isEdit ? $errors->updateQuotation : $errors->createQuotation;
    $hasOld  = $bag->any();
    $modalId = $isEdit ? 'editQuotationModal' : 'createQuotationModal';

    $customerId = $hasOld ? old('id_customer') : ($isEdit ? $quotation->id_customer : '');

    $rows = $hasOld
        ? array_values(old('items', []))
        : ($isEdit
            ? $quotation->details->map(fn ($d) => ['id_product' => $d->id_product, 'quantity' => (float) $d->quantity])->all()
            : [['id_product' => $products->first()?->id_product, 'quantity' => 1]]);

    if (! $rows) {
        $rows = [['id_product' => '', 'quantity' => 1]];
    }
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Title" aria-hidden="true" data-quotation-modal>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST"
              action="{{ $isEdit ? route('quotations.update', $quotation->id_quot) : route('quotations.store') }}"
              data-confirm="{{ $isEdit ? 'ต้องการบันทึกการแก้ไขใบเสนอราคานี้หรือไม่' : 'กรุณาตรวจสอบลูกค้า รายการสินค้า และยอดเงินให้ถูกต้องก่อนสร้างใบเสนอราคา' }}"
              data-confirm-title="{{ $isEdit ? 'ยืนยันการแก้ไขข้อมูล' : 'ยืนยันการสร้างใบเสนอราคา' }}"
              data-confirm-ok="{{ $isEdit ? 'บันทึกการแก้ไข' : 'สร้างใบเสนอราคา' }}"
              data-confirm-variant="{{ $isEdit ? 'primary' : 'success' }}">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Title">
                    @if ($isEdit)
                        <i class="bi bi-pencil-square me-1"></i>แก้ไขใบเสนอราคา
                        <span class="text-muted fw-normal fs-6">— {{ $quotation->code_quot }}</span>
                    @else
                        <i class="bi bi-file-earmark-plus me-1"></i>สร้างใบเสนอราคา
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body">
                @include('partials.modal-errors', ['bag' => $bag])

                {{-- ---------- ลูกค้า ---------- --}}
                <h6 class="modal-section"><i class="bi bi-person me-1"></i>ลูกค้า</h6>
                <div class="d-flex gap-2">
                    <select name="id_customer" class="form-select {{ $bag->has('id_customer') ? 'is-invalid' : '' }}"
                            required data-required-msg="กรุณาเลือกลูกค้า" data-customer-select>
                        <option value="">— เลือกลูกค้า —</option>
                        @foreach ($customers as $c)
                            @continue($c->isWalkIn())
                            <option value="{{ $c->id_customer }}" @selected((string) $customerId === (string) $c->id_customer)>
                                {{ $c->name_customer }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-secondary text-nowrap" data-quick-customer>
                        <i class="bi bi-person-plus me-1"></i>ลูกค้าใหม่
                    </button>
                </div>

                {{-- ---------- รายการสินค้า ---------- --}}
                <h6 class="modal-section mt-4"><i class="bi bi-list-ul me-1"></i>รายการสินค้า</h6>
                <div class="table-responsive">
                    <table class="table align-middle mb-2" data-items>
                        <thead class="table-light">
                            <tr>
                                <th>สินค้า</th>
                                <th style="width: 130px;">จำนวน (คิว)</th>
                                <th style="width: 150px;" class="text-end">ราคา/หน่วย</th>
                                <th style="width: 150px;" class="text-end">รวม</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                <tr data-row>
                                    <td>
                                        <select name="items[{{ $i }}][id_product]" class="form-select" data-product
                                                required data-required-msg="กรุณาเลือกสินค้า">
                                            <option value="">— เลือกสินค้า —</option>
                                            @foreach ($products as $p)
                                                <option value="{{ $p->id_product }}" data-price="{{ (float) $p->unit_price }}"
                                                    @selected((string) ($row['id_product'] ?? '') === (string) $p->id_product)>
                                                    {{ $p->name_product }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[{{ $i }}][quantity]"
                                               class="form-control text-end" value="{{ $row['quantity'] ?? 1 }}" data-qty
                                               required data-required-msg="กรุณาใส่จำนวน">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="items[{{ $i }}][price]"
                                               class="form-control text-end bg-light" readonly tabindex="-1" data-price-input>
                                    </td>
                                    <td class="text-end" data-line-total>0.00</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove title="ลบแถว">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-add-row>
                    <i class="bi bi-plus-lg me-1"></i>เพิ่มรายการ
                </button>
                <div class="form-text">ราคาต่อหน่วยดึงจากหน้าสินค้า · ผู้ดูแลระบบตรวจราคาอีกครั้งตอนอนุมัติ</div>

                <div class="row justify-content-end mt-3">
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">รวมเป็นเงิน</span><span data-sum-sub>0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">VAT {{ rtrim(rtrim(number_format(\App\Models\Quotation::DEFAULT_VAT_RATE, 2), '0'), '.') }}%</span><span data-sum-vat>0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-top fw-bold fs-5">
                            <span>ยอดสุทธิ</span><span data-sum-total>0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <span class="small text-muted me-auto">
                    {{ $isEdit ? 'แก้ไขได้เฉพาะใบที่ยังเป็นร่าง' : 'บันทึกเป็นฉบับร่าง แล้วรอผู้ดูแลระบบอนุมัติ' }}
                </span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกใบเสนอราคา' }}
                </button>
            </div>
        </form>
    </div>
</div>

@include('partials.modal-form')
{{-- ปุ่ม "ลูกค้าใหม่" เปิดหน้าต่างนี้แทน แล้วเลือกลูกค้าที่เพิ่งเพิ่มให้เลย --}}
@include('customers._quick-modal', ['action' => route('customers.store.ajax')])

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById(@json($modalId));
        const form    = modalEl.querySelector('form');
        const tbody   = form.querySelector('[data-items] tbody');
        const VAT     = {{ (float) \App\Models\Quotation::DEFAULT_VAT_RATE }};
        const money   = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        let nextIndex = tbody.querySelectorAll('[data-row]').length;

        /* ---------- รายการสินค้า ---------- */
        function calcRow(row) {
            const price = Number(row.querySelector('[data-product]').selectedOptions[0]?.dataset.price || 0);
            const qty   = Number(row.querySelector('[data-qty]').value || 0);
            row.querySelector("[data-price-input]").value = price ? price.toFixed(2) : '';
            row.querySelector('[data-line-total]').textContent = money(Math.round(price * qty * 100) / 100);
            return Math.round(price * qty * 100) / 100;
        }

        function recalc() {
            let sub = 0;
            tbody.querySelectorAll('[data-row]').forEach((row) => { sub += calcRow(row); });
            sub = Math.round(sub * 100) / 100;
            const vat = Math.round(sub * VAT) / 100;
            form.querySelector('[data-sum-sub]').textContent   = money(sub);
            form.querySelector('[data-sum-vat]').textContent   = money(vat);
            form.querySelector('[data-sum-total]').textContent = money(sub + vat);
        }

        tbody.addEventListener('input', recalc);
        tbody.addEventListener('change', recalc);
        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove]');
            if (!btn || tbody.querySelectorAll('[data-row]').length <= 1) return;
            btn.closest('tr').remove();
            recalc();
        });

        form.querySelector('[data-add-row]').addEventListener('click', () => {
            const row = tbody.querySelector('[data-row]').cloneNode(true);
            row.querySelectorAll('select, input').forEach((el) => {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + nextIndex + ']');
                el.classList.remove('is-invalid');
                if (el.matches('[data-qty]')) el.value = 1;
                else if (el.tagName === 'SELECT') el.value = '';
                else el.value = '';
            });
            nextIndex++;
            tbody.appendChild(row);
            recalc();
        });

        recalc();

        // กรอกผิดแล้วเด้งกลับ / มาจากลิงก์ ?modal=create หรือ ?edit=1 → เปิดหน้าต่างให้เอง
        @if ($hasOld || ($isEdit ? request()->boolean('edit') : request('modal') === 'create'))
            ModalForm.open(modalEl);
        @endif
    });
</script>
