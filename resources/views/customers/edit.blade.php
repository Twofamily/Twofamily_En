@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขข้อมูลลูกค้า</h3>
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

        <form method="POST" action="{{ route('customers.update', $customer) }}"
            data-confirm="ต้องการบันทึกการแก้ไขข้อมูลลูกค้ารายนี้หรือไม่" data-confirm-title="ยืนยันการแก้ไขข้อมูล" data-confirm-ok="บันทึกการแก้ไข" data-confirm-variant="primary">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">ชื่อลูกค้า</label>
                <input type="text" name="name_customer" class="form-control" data-required-msg="กรุณาใส่ชื่อลูกค้า"
                    value="{{ old('name_customer', $customer->name_customer) }}"
                    placeholder="เช่น บริษัท ทูแฟมิลี่ เอ็นจิเนียริ่ง จำกัด หรือ นายกรภัทร สิงวะราช" required>
            </div>

            <div class="mb-3">
                <label class="form-label">ประเภทลูกค้า</label>
                <select name="customer_type" class="form-select" required>
                    <option value="person" @selected(old('customer_type', $customer->customer_type) == 'person')>
                        บุคคล
                    </option>
                    <option value="company" @selected(old('customer_type', $customer->customer_type) == 'company')>
                        บริษัท
                    </option>
                </select>
                <div class="form-text text-muted">
                    เลือกประเภทของลูกค้า
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">เบอร์โทร (10 หลัก)</label>
                <input type="text" name="phone_customer" class="form-control" maxlength="10" pattern="[0-9]{10}" data-pattern-msg="กรุณาใส่เบอร์โทรเป็นตัวเลข 10 หลัก"
                    inputmode="numeric" data-digits-only value="{{ old('phone_customer', $customer->phone_customer) }}"
                    placeholder="เช่น 0812345678">
            </div>

            <div class="mb-3">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email_customer" class="form-control"
                    value="{{ old('email_customer', $customer->email_customer) }}" placeholder="เช่น example@email.com">
            </div>

            <div class="mb-3">
                <label class="form-label">บ้านเลขที่ / หมู่</label>
                <input type="text" name="address_detail" class="form-control"
                    value="{{ old('address_detail', $customer->address_detail) }}" placeholder="เช่น 123/45 หมู่ 6">
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">ตำบล</label>
                    <input type="text" name="subdistrict" class="form-control"
                        value="{{ old('subdistrict', $customer->subdistrict) }}" placeholder="เช่น ศิลา">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">อำเภอ</label>
                    <input type="text" name="district" class="form-control"
                        value="{{ old('district', $customer->district) }}" placeholder="เช่น เมืองขอนแก่น">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">จังหวัด</label>
                    <select name="province" class="form-select">
                        <option value="">— เลือกจังหวัด —</option>
                        @foreach ($provinces as $p)
                            <option value="{{ $p }}" @selected(old('province', $customer->province) == $p)>
                                {{ $p }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">รหัสไปรษณีย์</label>
                <input type="text" name="zipcode" class="form-control" maxlength="5" pattern="[0-9]{5}" data-pattern-msg="กรุณาใส่รหัสไปรษณีย์เป็นตัวเลข 5 หลัก"
                    inputmode="numeric" data-digits-only value="{{ old('zipcode', $customer->zipcode) }}" placeholder="เช่น 40000">
            </div>

            <div class="d-flex gap-2 pt-2">
                <button type="submit" class="btn btn-dark">บันทึกการแก้ไข</button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                    ยกเลิก
                </a>
            </div>

        </form>
    </div>
@endsection
