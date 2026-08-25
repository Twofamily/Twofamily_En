@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขพนักงานขับรถ</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">


        @if ($errors->any())
            <div class="alert alert-danger shadow-sm">
                <div class="fw-semibold mb-1">กรุณาตรวจสอบข้อมูล:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('drivers.update', $driver) }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                    <input type="text" name="fname_driver"
                        class="form-control @error('fname_driver') is-invalid @enderror" placeholder="เช่น สมชาย"
                        value="{{ old('fname_driver', $driver->fname_driver ?? '') }}" required>
                    @error('fname_driver')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                    <input type="text" name="lname_driver"
                        class="form-control @error('lname_driver') is-invalid @enderror" placeholder="เช่น ใจดี"
                        value="{{ old('lname_driver', $driver->lname_driver ?? '') }}" required>
                    @error('lname_driver')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mt-2">

                <div class="col-md-12">
                    <label class="form-label">รายละเอียดที่อยู่ (บ้านเลขที่ / หมู่ / ซอย)</label>
                    <input type="text" name="address_detail"
                        class="form-control @error('address_detail') is-invalid @enderror"
                        value="{{ old('address_detail', $driver->address_detail ?? '') }}"
                        placeholder="เช่น 123/4 หมู่ 5 ซอยสุขสมบูรณ์">
                    @error('address_detail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <input type="hidden" id="old_province" value="{{ old('province', $driver->province ?? '') }}">
                <input type="hidden" id="old_district" value="{{ old('district', $driver->district ?? '') }}">
                <input type="hidden" id="old_subdistrict" value="{{ old('subdistrict', $driver->subdistrict ?? '') }}">

                <div class="col-md-6">
                    <label class="form-label">จังหวัด</label>
                    <select name="province" id="province" class="form-select @error('province') is-invalid @enderror">
                        <option value="">— เลือกจังหวัด —</option>
                    </select>
                    @error('province')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">อำเภอ/เขต</label>
                    <select name="district" id="district" class="form-select @error('district') is-invalid @enderror"
                        disabled>
                        <option value="">— เลือกอำเภอ —</option>
                    </select>
                    @error('district')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">ตำบล/แขวง</label>
                    <select name="subdistrict" id="subdistrict"
                        class="form-select @error('subdistrict') is-invalid @enderror" disabled>
                        <option value="">— เลือกตำบล —</option>
                    </select>
                    @error('subdistrict')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">รหัสไปรษณีย์</label>
                    <input type="text" name="zipcode" id="zipcode"
                        class="form-control bg-light @error('zipcode') is-invalid @enderror"
                        value="{{ old('zipcode', $driver->zipcode ?? '') }}" readonly placeholder="รหัสไปรษณีย์อัตโนมัติ">
                    @error('zipcode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3 mt-4">
                <label class="form-label">เบอร์โทร (10 หลัก)</label>
                <input type="text" name="phone_driver" class="form-control @error('phone_driver') is-invalid @enderror"
                    maxlength="10" pattern="[0-9]{10}" inputmode="numeric" placeholder="เช่น 0812345678"
                    value="{{ old('phone_driver', $driver->phone_driver ?? '') }}">
                @error('phone_driver')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">เลขบัตรประชาชน (13 หลัก)</label>
                <input type="text" name="citizenid_driver"
                    class="form-control @error('citizenid_driver') is-invalid @enderror" maxlength="13" pattern="[0-9]{13}"
                    inputmode="numeric" placeholder="เช่น 1234567890123"
                    value="{{ old('citizenid_driver', $driver->citizenid_driver ?? '') }}">
                @error('citizenid_driver')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">รูปใบขับขี่</label>

                <input type="file" name="citizen_image"
                    class="form-control @error('citizen_image') is-invalid @enderror">

                @error('citizen_image')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                @if ($driver->citizen_image)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $driver->citizen_image) }}" width="150"
                            class="border rounded">
                    </div>
                @endif
            </div>

            <button class="btn btn-dark">บันทึกการแก้ไข</button>
            <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const apiURL =
                'https://raw.githubusercontent.com/kongvut/thai-province-data/master/api/latest/province_with_district_and_sub_district.json';
            let thaiData = [];

            const provinceSelect = document.getElementById('province');
            const districtSelect = document.getElementById('district');
            const subdistrictSelect = document.getElementById('subdistrict');
            const zipcodeInput = document.getElementById('zipcode');

            // ดึงค่าเก่าออกมา (ในกรณีนี้จะเป็นค่าเก่าจาก Database)
            const oldProvince = document.getElementById('old_province').value;
            const oldDistrict = document.getElementById('old_district').value;
            const oldSubdistrict = document.getElementById('old_subdistrict').value;

            // 1. โหลดข้อมูล JSON
            fetch(apiURL)
                .then(response => response.json())
                .then(data => {
                    thaiData = data;
                    populateProvinces();
                })
                .catch(error => console.error('Error loading Thai Data:', error));

            // 2. ใส่ข้อมูลจังหวัด
            function populateProvinces() {
                thaiData.forEach(prov => {
                    const option = new Option(prov.name_th, prov.name_th);
                    if (prov.name_th === oldProvince) option.selected = true;
                    provinceSelect.add(option);
                });

                if (oldProvince) {
                    provinceSelect.dispatchEvent(new Event('change'));
                }
            }

            // 3. เมื่อเปลี่ยนจังหวัด -> ใส่อำเภอ
            provinceSelect.addEventListener('change', function() {
                districtSelect.innerHTML = '<option value="">— เลือกอำเภอ —</option>';
                subdistrictSelect.innerHTML = '<option value="">— เลือกตำบล —</option>';
                zipcodeInput.value = '';

                if (!this.value) {
                    districtSelect.disabled = true;
                    subdistrictSelect.disabled = true;
                    return;
                }

                const selectedProv = thaiData.find(p => p.name_th === this.value);
                if (selectedProv) {
                    const districts = selectedProv.amphure || selectedProv.district || selectedProv
                        .districts || [];

                    districts.forEach(dist => {
                        const option = new Option(dist.name_th, dist.name_th);
                        if (dist.name_th === oldDistrict && this.value === oldProvince) option
                            .selected = true;
                        districtSelect.add(option);
                    });

                    districtSelect.disabled = false;

                    if (districtSelect.value) {
                        districtSelect.dispatchEvent(new Event('change'));
                    }
                }
            });

            // 4. เมื่อเปลี่ยนอำเภอ -> ใส่ตำบล
            districtSelect.addEventListener('change', function() {
                subdistrictSelect.innerHTML = '<option value="">— เลือกตำบล —</option>';
                zipcodeInput.value = '';

                if (!this.value) {
                    subdistrictSelect.disabled = true;
                    return;
                }

                const selectedProv = thaiData.find(p => p.name_th === provinceSelect.value);
                if (selectedProv) {
                    const districts = selectedProv.amphure || selectedProv.district || selectedProv
                        .districts || [];
                    const selectedDist = districts.find(d => d.name_th === this.value);

                    if (selectedDist) {
                        const subDistricts = selectedDist.tambon || selectedDist.sub_district ||
                            selectedDist.sub_districts || [];

                        subDistricts.forEach(sub => {
                            const option = new Option(sub.name_th, sub.name_th);
                            if (sub.name_th === oldSubdistrict && this.value === oldDistrict) option
                                .selected = true;
                            subdistrictSelect.add(option);
                        });

                        subdistrictSelect.disabled = false;

                        if (subdistrictSelect.value) {
                            subdistrictSelect.dispatchEvent(new Event('change'));
                        }
                    }
                }
            });

            // 5. เมื่อเปลี่ยนตำบล -> ใส่รหัสไปรษณีย์
            subdistrictSelect.addEventListener('change', function() {
                zipcodeInput.value = '';
                if (!this.value) return;

                const selectedProv = thaiData.find(p => p.name_th === provinceSelect.value);
                if (selectedProv) {
                    const districts = selectedProv.amphure || selectedProv.district || selectedProv
                        .districts || [];
                    const selectedDist = districts.find(d => d.name_th === districtSelect.value);

                    if (selectedDist) {
                        const subDistricts = selectedDist.tambon || selectedDist.sub_district ||
                            selectedDist.sub_districts || [];
                        const selectedSub = subDistricts.find(s => s.name_th === this.value);

                        if (selectedSub) {
                            zipcodeInput.value = selectedSub.zip_code;
                        }
                    }
                }
            });
        });
    </script>
@endsection
