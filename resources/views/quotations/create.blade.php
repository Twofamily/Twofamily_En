@extends('layout')

@section('content')
    <div class="container">
        <h3>สร้างใบเสนอราคา</h3>

        <form method="POST" action="{{ route('quotations.store') }}">
            @csrf

            <div class="mb-3">
                <label>ลูกค้า</label>

                <div class="d-flex gap-2">
                    <select name="id_customer" id="customerSelect" class="form-select" required>
                        <option value="">— เลือกลูกค้า —</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id_customer }}">
                                {{ $c->name_customer }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" class="btn btn-primary" id="toggleCustomerForm">
                        + เพิ่มลูกค้า
                    </button>
                </div>

                <div id="customerForm" class="card shadow-sm border-0 mt-3" style="display:none;">
                    <div class="card-body">

                        <h5 class="mb-3">เพิ่มลูกค้า</h5>

                        <div class="mb-3">
                            <label class="form-label">ชื่อลูกค้า</label>
                            <input type="text" id="name_customer" class="form-control"
                                placeholder="เช่น บริษัท ทูแฟมิลี่ เอ็นจิเนียริ่ง จำกัด หรือ นายกรภัทร สิงวะราช">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ประเภทลูกค้า</label>
                            <select id="customer_type" class="form-select">
                                <option value="person">บุคคล</option>
                                <option value="company">บริษัท</option>
                            </select>
                            <div class="form-text text-muted">
                                เลือกประเภทของลูกค้า
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">เบอร์โทร (10 หลัก)</label>
                            <input type="text" id="phone_customer" class="form-control" placeholder="เช่น 0812345678">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">อีเมล</label>
                            <input type="email" id="email_customer" class="form-control"
                                placeholder="เช่น example@email.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">บ้านเลขที่ / หมู่</label>
                            <input type="text" id="address_detail" class="form-control" placeholder="เช่น 123/45 หมู่ 6">
                        </div>

                        <div class="row g-3">

                            <div class="col-md-3">
                                <label class="form-label">จังหวัด</label>
                                <select id="province" class="form-select">
                                    <option value="">— เลือกจังหวัด —</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">อำเภอ</label>
                                <select id="district" class="form-select" disabled>
                                    <option value="">— เลือกอำเภอ —</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">ตำบล</label>
                                <select id="subdistrict" class="form-select" disabled>
                                    <option value="">— เลือกตำบล —</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">รหัสไปรษณีย์</label>
                                <input type="text" id="zipcode" class="form-control bg-light" readonly>
                            </div>

                        </div>

                        <div class="mt-4 d-flex justify-content-end">
                            <button type="button" class="btn btn-success" id="saveCustomer">
                                บันทึกลูกค้า
                            </button>
                        </div>

                    </div>
                </div><br>

                <table class="table table-bordered" id="itemsTable">
                    <thead>
                        <tr>
                            <th>สินค้า</th>
                            <th width="120">จำนวน (คิว)</th>
                            <th width="150">ราคา/หน่วย</th>
                            <th width="150">รวม</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="items[0][id_product]" class="form-select product">
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id_product }}" data-price="{{ $p->unit_price }}">
                                            {{ $p->name_product }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][quantity]" class="form-control qty" value="1">
                            </td>
                            <td>
                                <input type="number" name="items[0][price]" class="form-control price" readonly>
                            </td>
                            <td>
                                <input type="number" class="form-control total" readonly>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger remove">ลบ</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="button" id="addRow" class="btn btn-secondary mb-3">
                    + เพิ่มรายการ
                </button>

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
                                <th class="text-end align-middle">ส่วนลด</th>
                                <td>
                                    <input type="number" name="discount" id="discount" class="form-control text-end"
                                        value="0" min="0">
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
                    <button type="submit" class="btn btn-success">บันทึกใบเสนอราคา</button>
                    <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
                </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

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
                if (e.target.classList.contains('product') || e.target.classList.contains('qty')) {
                    const row = e.target.closest('tr');
                    calcRow(row);
                    calculateSummary();
                }
            });

            discountInput.addEventListener('input', calculateSummary);

            document.getElementById('addRow').onclick = () => {
                const tbody = document.querySelector('#itemsTable tbody');
                const row = tbody.children[0].cloneNode(true);

                row.querySelectorAll('input, select').forEach(el => {
                    if (el.name) el.name = el.name.replace(/\[\d+]/, `[${index}]`);
                    if (el.tagName === 'INPUT') {
                        el.value = el.classList.contains('qty') ? 1 : '';
                    }
                });

                tbody.appendChild(row);
                index++;
            };

            document.addEventListener('click', e => {
                if (e.target.classList.contains('remove')) {
                    const tbody = document.querySelector('#itemsTable tbody');
                    if (tbody.children.length > 1) {
                        e.target.closest('tr').remove();
                        calculateSummary();
                    }
                }
            });

            document.querySelectorAll('#itemsTable tbody tr').forEach(row => calcRow(row));
            calculateSummary();

            document.getElementById('toggleCustomerForm').onclick = function() {
                const form = document.getElementById('customerForm');
                form.style.display = (form.style.display === 'none') ? 'block' : 'none';
            };

            const apiURL =
                'https://raw.githubusercontent.com/kongvut/thai-province-data/master/api/latest/province_with_district_and_sub_district.json';

            let thaiData = [];

            const province = document.getElementById('province');
            const district = document.getElementById('district');
            const subdistrict = document.getElementById('subdistrict');
            const zipcode = document.getElementById('zipcode');

            fetch(apiURL)
                .then(res => res.json())
                .then(data => {
                    thaiData = data;

                    data.forEach(p => {
                        province.add(new Option(p.name_th, p.name_th));
                    });
                })
                .catch(err => console.error("โหลดจังหวัดไม่ได้", err));

            province.addEventListener('change', function() {

                district.innerHTML = '<option value="">— เลือกอำเภอ —</option>';
                subdistrict.innerHTML = '<option value="">— เลือกตำบล —</option>';
                zipcode.value = '';

                district.disabled = true;
                subdistrict.disabled = true;

                const p = thaiData.find(x => x.name_th === this.value);
                if (!p) return;

                const districts = p.amphure || p.districts || p.district || [];

                districts.forEach(d => {
                    district.add(new Option(d.name_th, d.name_th));
                });

                district.disabled = false;
            });

            district.addEventListener('change', function() {

                subdistrict.innerHTML = '<option value="">— เลือกตำบล —</option>';
                zipcode.value = '';

                const p = thaiData.find(x => x.name_th === province.value);
                if (!p) return;

                const districts = p.amphure || p.districts || p.district || [];
                const d = districts.find(x => x.name_th === this.value);
                if (!d) return;

                const subs = d.tambon || d.sub_districts || d.subdistricts || [];

                subs.forEach(s => {
                    let option = new Option(s.name_th, s.name_th);
                    option.dataset.zip = s.zip_code;
                    subdistrict.add(option);
                });

                subdistrict.disabled = false;
            });

            subdistrict.addEventListener('change', function() {
                zipcode.value = this.selectedOptions[0]?.dataset?.zip || '';
            });

            document.getElementById('saveCustomer').onclick = function() {

                const data = {
                    name_customer: document.getElementById('name_customer').value,
                    customer_type: document.getElementById('customer_type').value,
                    phone_customer: document.getElementById('phone_customer').value,
                    email_customer: document.getElementById('email_customer').value,
                    address_detail: document.getElementById('address_detail').value,
                    subdistrict: document.getElementById('subdistrict').value,
                    district: document.getElementById('district').value,
                    province: document.getElementById('province').value,
                    zipcode: document.getElementById('zipcode').value
                };

                fetch("{{ route('customers.store.ajax') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify(data)
                    })
                    .then(res => res.json())
                    .then(res => {

                        let select = document.getElementById('customerSelect');
                        let option = new Option(res.name_customer, res.id_customer, true, true);
                        select.add(option);

                        document.getElementById('customerForm').style.display = 'none';
                    });
            };

        });
    </script>
@endsection
