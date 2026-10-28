@extends('layout')

@section('namepage')
    <div class="container">
        <h3>เพิ่มลูกค้า</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">

        <form method="POST" action="{{ route('customers.store') }}"
            data-confirm="ต้องการบันทึกข้อมูลลูกค้ารายนี้หรือไม่" data-confirm-title="ยืนยันการบันทึกข้อมูล" data-confirm-ok="บันทึก" data-confirm-variant="primary">
            @csrf

            <div class="mb-3">
                <label class="form-label">ชื่อลูกค้า</label>
                <input type="text" name="name_customer" class="form-control" data-required-msg="กรุณาใส่ชื่อลูกค้า"
                    placeholder="เช่น บริษัท ทูแฟมิลี่ เอ็นจิเนียริ่ง จำกัด หรือ นายกรภัทร สิงวะราช" required>
            </div>

            <div class="mb-3">
                <label class="form-label">ประเภทลูกค้า</label>
                <select name="customer_type" class="form-select" required>
                    <option value="person">บุคคล</option>
                    <option value="company">บริษัท</option>
                </select>
                <div class="form-text text-muted">
                    เลือกประเภทของลูกค้า
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">เบอร์โทร (10 หลัก)</label>
                <input type="text" name="phone_customer" class="form-control" maxlength="10" pattern="[0-9]{10}" data-pattern-msg="กรุณาใส่เบอร์โทรเป็นตัวเลข 10 หลัก"
                    inputmode="numeric" data-digits-only placeholder="เช่น 0812345678">
            </div>

            <div class="mb-3">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email_customer" class="form-control" placeholder="เช่น example@email.com">
            </div>

            <div class="mb-3">
                <label class="form-label">บ้านเลขที่ / หมู่</label>
                <input type="text" name="address_detail" class="form-control" placeholder="เช่น 123/45 หมู่ 6">
            </div>

            <div class="row g-3 mt-2">

                <div class="col-md-3">
                    <label>จังหวัด</label>
                    <select id="province" name="province" class="form-select" required>
                        <option value="">— เลือกจังหวัด —</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>อำเภอ</label>
                    <select id="district" name="district" class="form-select" disabled required>
                        <option value="">— เลือกอำเภอ —</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>ตำบล</label>
                    <select id="subdistrict" name="subdistrict" class="form-select" disabled required>
                        <option value="">— เลือกตำบล —</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>รหัสไปรษณีย์</label>
                    <input type="text" id="zipcode" name="zipcode" class="form-control bg-light" readonly>
                </div>

            </div><br>

            <button class="btn btn-dark">บันทึก</button>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>

        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const apiURL =
                @json(asset('data/thai-address.json'));

            let thaiData = [];

            const province = document.getElementById('province');
            const district = document.getElementById('district');
            const subdistrict = document.getElementById('subdistrict');
            const zipcode = document.getElementById('zipcode');

            // 🔥 เช็คว่าหา element เจอไหม
            console.log(province, district, subdistrict);

            // โหลดข้อมูลจังหวัด
            fetch(apiURL)
                .then(res => res.json())
                .then(data => {
                    thaiData = data;

                    console.log("โหลดจังหวัดสำเร็จ", data.length);

                    data.forEach(p => {
                        province.add(new Option(p.name_th, p.name_th));
                    });
                })
                .catch(err => {
                    console.error("โหลด API ไม่ได้", err);
                });

            // จังหวัด → อำเภอ
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

                subdistrict.disabled = true;

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

            // ตำบล → zipcode
            subdistrict.addEventListener('change', function() {
                const selected = this.selectedOptions[0];
                zipcode.value = selected?.dataset?.zip || '';
            });

        });
    </script>
@endsection
