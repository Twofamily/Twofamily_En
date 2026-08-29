@extends('layout')

@section('content')
    <div class="container">
        <h3>แก้ไขใบเสนอราคา</h3>

        <form method="POST" action="{{ route('quotations.update', $quotation->id_quot) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label>ลูกค้า</label>
                <select name="id_customer" class="form-select">
                    @foreach ($customers as $c)
                        <option value="{{ $c->id_customer }}" @selected($quotation->id_customer == $c->id_customer)>
                            {{ $c->name_customer }}
                        </option>
                    @endforeach
                </select>
            </div>

            <table class="table table-bordered" id="itemsTable">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th width="120">จำนวน</th>
                        <th width="150">ราคา</th>
                        <th width="150">รวม</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->details as $i => $d)
                        <tr>
                            <td>
                                <select name="items[{{ $i }}][id_product]" class="form-select product">
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id_product }}" data-price="{{ $p->unit_price }}"
                                            @selected($p->id_product == $d->id_product)>
                                            {{ $p->name_product }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[{{ $i }}][quantity]" class="form-control qty"
                                    value="{{ $d->quantity }}">
                            </td>
                            <td>
                                <input type="number" name="items[{{ $i }}][price]" class="form-control price"
                                    value="{{ $d->price_per_unit }}" readonly>
                            </td>
                            <td>
                                <input type="number" class="form-control total" value="{{ $d->total_price }}" readonly>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger remove">ลบ</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="row justify-content-end mt-4">
                <div class="col-md-5">
                    <table class="table table-borderless">
                        <tr>
                            <th class="text-end">ยอดรวมก่อนส่วนลด</th>
                            <td class="text-end">
                                <span id="subTotal">0.00</span> บาท
                            </td>
                        </tr>

                        <tr>
                            <th class="text-end">ส่วนลด</th>
                            <td>
                                <input type="number" name="discount" id="discount" class="form-control text-end"
                                    value="{{ $quotation->discount ?? 0 }}">
                            </td>
                        </tr>

                        <tr>
                            <th class="text-end">VAT 7%</th>
                            <td class="text-end">
                                <span id="vatAmount">0.00</span> บาท
                            </td>
                        </tr>

                        <tr class="table-primary fw-bold">
                            <th class="text-end">ยอดสุทธิ</th>
                            <td class="text-end">
                                <span id="grandTotal">0.00</span> บาท
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-success">บันทึกการแก้ไข</button>
                <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>

    <script>
        let index = 1;

        const subTotalEl = document.getElementById('subTotal');
        const vatEl = document.getElementById('vatAmount');
        const grandTotalEl = document.getElementById('grandTotal');
        const discountInput = document.getElementById('discount');

        function calcRow(row) {
            const price = Number(
                row.querySelector('.product').selectedOptions[0]?.dataset.price || 0
            );
            const qty = Number(row.querySelector('.qty').value || 0);

            row.querySelector('.price').value = price;
            row.querySelector('.total').value = price * qty;
        }

        function calculateSummary() {
            let subTotal = 0;

            document.querySelectorAll('.total').forEach(input => {
                subTotal += Number(input.value || 0);
            });

            let discount = Number(discountInput.value || 0);
            let afterDiscount = Math.max(subTotal - discount, 0);
            let vat = afterDiscount * 0.07;
            let grandTotal = afterDiscount + vat;

            subTotalEl.textContent = subTotal.toFixed(2);
            vatEl.textContent = vat.toFixed(2);
            grandTotalEl.textContent = grandTotal.toFixed(2);
        }

        document.addEventListener('input', e => {
            if (
                e.target.classList.contains('product') ||
                e.target.classList.contains('qty')
            ) {
                const row = e.target.closest('tr');
                calcRow(row);
                calculateSummary();
            }
        });

        discountInput.addEventListener('input', calculateSummary);

        document.addEventListener('click', e => {
            if (e.target.classList.contains('remove')) {
                const tbody = document.querySelector('#itemsTable tbody');
                if (tbody.children.length > 1) {
                    e.target.closest('tr').remove();
                    calculateSummary();
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
                calcRow(row);
            });
            calculateSummary();
        });
    </script>
@endsection
