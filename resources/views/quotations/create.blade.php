@extends('layout')

@section('namepage')
    <div class="container">
        <h3>สร้างใบเสนอราคา</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <form method="POST" action="{{ route('quotations.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">ลูกค้า</label>

                <div class="d-flex gap-2">
                    <select name="id_customer" id="customerSelect" class="form-select" required>
                        <option value="">— เลือกลูกค้า —</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id_customer }}" @selected(old('id_customer') == $c->id_customer)>
                                {{ $c->name_customer }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" class="btn btn-outline-secondary text-nowrap" id="toggleCustomerForm">
                        + เพิ่มลูกค้า
                    </button>
                </div>

                @error('id_customer')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                <div id="customerForm" class="border rounded-3 mt-3" style="display:none;">
                    <div class="p-3">

                        <h6 class="mb-3">เพิ่มลูกค้าใหม่</h6>

                        <div class="mb-3">
                            <label class="form-label">ชื่อลูกค้า</label>
                            <input type="text" id="cust_name" class="form-control"
                                placeholder="เช่น บริษัท ทูแฟมิลี่ เอ็นจิเนียริ่ง จำกัด">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ประเภทลูกค้า</label>
                            <select id="cust_type" class="form-select">
                                <option value="person">บุคคล</option>
                                <option value="company">บริษัท</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">เบอร์โทร (10 หลัก)</label>
                            <input type="text" id="cust_phone" class="form-control" maxlength="10"
                                inputmode="numeric" placeholder="เช่น 0812345678">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">อีเมล</label>
                            <input type="email" id="cust_email" class="form-control"
                                placeholder="เช่น example@email.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">บ้านเลขที่ / หมู่</label>
                            <input type="text" id="cust_address" class="form-control" placeholder="เช่น 123/45 หมู่ 6">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">จังหวัด</label>
                                <select id="cust_province" class="form-select">
                                    <option value="">— เลือกจังหวัด —</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">อำเภอ</label>
                                <select id="cust_district" class="form-select" disabled>
                                    <option value="">— เลือกอำเภอ —</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">ตำบล</label>
                                <select id="cust_subdistrict" class="form-select" disabled>
                                    <option value="">— เลือกตำบล —</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">รหัสไปรษณีย์</label>
                                <input type="text" id="cust_zipcode" class="form-control bg-light" readonly>
                            </div>
                        </div>

                        <div id="cust_error" class="alert alert-danger mt-3 mb-0 d-none"></div>

                        <div class="mt-3 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="cancelCustomer">
                                ยกเลิก
                            </button>
                            <button type="button" class="btn btn-dark" id="saveCustomer">
                                บันทึกลูกค้า
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <hr class="my-4">

            <table class="table table-bordered" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th>สินค้า</th>
                        <th width="120">จำนวน (คิว)</th>
                        <th width="150">ราคา/หน่วย</th>
                        <th width="150">รวม</th>
                        <th width="80"></th>
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
                            <input type="number" step="0.01" min="0" name="items[0][quantity]"
                                class="form-control qty" value="1">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="items[0][price]" class="form-control price" readonly>
                        </td>
                        <td>
                            <input type="number" step="0.01" class="form-control total" readonly>
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-danger remove">ลบ</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="button" id="addRow" class="btn btn-outline-secondary mb-3">
                + เพิ่มรายการ
            </button>

            <div class="row justify-content-end mt-4">
                <div class="col-md-5">
                    <table class="table table-borderless">
                        <tr>
                            <th class="text-end">ยอดรวมก่อนส่วนลด</th>
                            <td class="text-end"><span id="subTotal">0.00</span> บาท</td>
                        </tr>
                        <tr>
                            <th class="text-end align-middle">ส่วนลด</th>
                            <td>
                                <input type="number" name="discount" id="discount"
                                    class="form-control text-end" value="{{ old('discount', 0) }}" min="0">
                            </td>
                        </tr>
                        <tr>
                            <th class="text-end">VAT 7%</th>
                            <td class="text-end"><span id="vatAmount">0.00</span> บาท</td>
                        </tr>
                        <tr class="fw-bold border-top">
                            <th class="text-end">ยอดสุทธิ</th>
                            <td class="text-end"><span id="grandTotal">0.00</span> บาท</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-dark">บันทึกใบเสนอราคา</button>
                <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ---------- รายการสินค้า ---------- */

            let index = 1;

            const subTotalEl = document.getElementById('subTotal');
            const vatEl = document.getElementById('vatAmount');
            const grandTotalEl = document.getElementById('grandTotal');
            const discountInput = document.getElementById('discount');

            function calcRow(row) {
                const price = Number(row.querySelector('.product').selectedOptions[0]?.dataset.price || 0);
                const qty = Number(row.querySelector('.qty').value || 0);

                row.querySelector('.price').value = price;
                row.querySelector('.total').value = (price * qty).toFixed(2);
            }

            function calculateSummary() {
                let subTotal = 0;

                document.querySelectorAll('.total').forEach(input => {
                    subTotal += Number(input.value || 0);
                });

                const discount = Math.min(Number(discountInput.value || 0), subTotal);
                const afterDiscount = Math.max(subTotal - discount, 0);
                const vat = afterDiscount * 0.07;

                subTotalEl.textContent = subTotal.toFixed(2);
                vatEl.textContent = vat.toFixed(2);
                grandTotalEl.textContent = (afterDiscount + vat).toFixed(2);
            }

            document.addEventListener('input', e => {
                if (e.target.classList.contains('product') || e.target.classList.contains('qty')) {
                    calcRow(e.target.closest('tr'));
                    calculateSummary();
                }
            });

            document.addEventListener('change', e => {
                if (e.target.classList.contains('product')) {
                    calcRow(e.target.closest('tr'));
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
                calcRow(row);
                calculateSummary();
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

            /* ---------- ฟอร์มเพิ่มลูกค้า ---------- */

            const customerForm = document.getElementById('customerForm');
            const errorBox = document.getElementById('cust_error');

            const province = document.getElementById('cust_province');
            const district = document.getElementById('cust_district');
            const subdistrict = document.getElementById('cust_subdistrict');
            const zipcode = document.getElementById('cust_zipcode');

            function showError(message) {
                errorBox.textContent = message;
                errorBox.classList.remove('d-none');
            }

            function clearError() {
                errorBox.textContent = '';
                errorBox.classList.add('d-none');
            }

            document.getElementById('toggleCustomerForm').onclick = function() {
                customerForm.style.display = (customerForm.style.display === 'none') ? 'block' : 'none';
                clearError();
            };

            document.getElementById('cancelCustomer').onclick = function() {
                customerForm.style.display = 'none';
                clearError();
            };

            const apiURL =
                'https://raw.githubusercontent.com/kongvut/thai-province-data/master/api/latest/province_with_district_and_sub_district.json';

            let thaiData = [];

            fetch(apiURL)
                .then(res => res.json())
                .then(data => {
                    thaiData = data;
                    data.forEach(p => province.add(new Option(p.name_th, p.name_th)));
                })
                .catch(err => console.error('โหลดข้อมูลจังหวัดไม่ได้', err));

            const getDistricts = p => p?.amphure || p?.districts || p?.district || [];
            const getSubs = d => d?.tambon || d?.sub_districts || d?.subdistricts || [];

            province.addEventListener('change', function() {
                district.innerHTML = '<option value="">— เลือกอำเภอ —</option>';
                subdistrict.innerHTML = '<option value="">— เลือกตำบล —</option>';
                zipcode.value = '';
                district.disabled = true;
                subdistrict.disabled = true;

                const p = thaiData.find(x => x.name_th === this.value);
                if (!p) return;

                getDistricts(p).forEach(d => district.add(new Option(d.name_th, d.name_th)));
                district.disabled = false;
            });

            district.addEventListener('change', function() {
                subdistrict.innerHTML = '<option value="">— เลือกตำบล —</option>';
                zipcode.value = '';
                subdistrict.disabled = true;

                const p = thaiData.find(x => x.name_th === province.value);
                if (!p) return;

                const d = getDistricts(p).find(x => x.name_th === this.value);
                if (!d) return;

                getSubs(d).forEach(s => {
                    const option = new Option(s.name_th, s.name_th);
                    option.dataset.zip = s.zip_code || '';
                    subdistrict.add(option);
                });

                subdistrict.disabled = false;
            });

            subdistrict.addEventListener('change', function() {
                zipcode.value = this.selectedOptions[0]?.dataset?.zip || '';
            });

            document.getElementById('saveCustomer').onclick = async function() {

                const btn = this;
                const name = document.getElementById('cust_name').value.trim();

                clearError();

                if (!name) {
                    showError('กรุณากรอกชื่อลูกค้า');
                    return;
                }

                const payload = {
                    name_customer: name,
                    customer_type: document.getElementById('cust_type').value,
                    phone_customer: document.getElementById('cust_phone').value.trim(),
                    email_customer: document.getElementById('cust_email').value.trim(),
                    address_detail: document.getElementById('cust_address').value.trim(),
                    subdistrict: subdistrict.value,
                    district: district.value,
                    province: province.value,
                    zipcode: zipcode.value
                };

                btn.disabled = true;
                btn.textContent = 'กำลังบันทึก...';

                try {
                    const res = await fetch("{{ route('customers.store.ajax') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (res.status === 422) {
                        const err = await res.json();
                        showError(Object.values(err.errors || {}).flat().join(' · '));
                        return;
                    }

                    if (res.status === 419) {
                        showError('เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่');
                        return;
                    }

                    if (!res.ok) {
                        console.error('storeAjax error', res.status, await res.text());
                        showError('บันทึกไม่สำเร็จ (รหัส ' + res.status + ') กรุณาดูรายละเอียดใน Console');
                        return;
                    }

                    const result = await res.json();
                    const customer = result.customer ?? result;

                    if (!customer.id_customer) {
                        console.error('unexpected response', result);
                        showError('เซิร์ฟเวอร์ตอบกลับผิดรูปแบบ กรุณาดูรายละเอียดใน Console');
                        return;
                    }

                    const select = document.getElementById('customerSelect');
                    select.add(new Option(customer.name_customer, customer.id_customer, true, true));

                    customerForm.style.display = 'none';

                    ['cust_name', 'cust_phone', 'cust_email', 'cust_address']
                        .forEach(id => document.getElementById(id).value = '');

                } catch (error) {
                    console.error('network error', error);
                    showError('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'บันทึกลูกค้า';
                }
            };

        });
    </script>
@endsection