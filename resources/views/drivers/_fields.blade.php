{{--
    ช่องกรอกพนักงานขับรถ — ใช้ทั้งหน้าต่างเพิ่มและแก้ไข (วางไว้ "ข้างใน <form>")
    @include('drivers._fields', ['p' => 'c_', 'v' => [...], 'bag' => $errors->createDriver])
--}}
@php
    $v   = $v ?? [];
    $bag = $bag ?? new \Illuminate\Support\MessageBag();
    $val = fn (string $key) => $v[$key] ?? '';
    $bad = fn (string $key) => $bag->has($key) ? 'is-invalid' : '';
@endphp

<div class="row g-3">
    {{-- ---------- รูปใบขับขี่ ---------- --}}
    <div class="col-md-4">
        <label class="form-label" for="{{ $p }}citizen_image">รูปใบขับขี่</label>
        <div class="border rounded bg-light d-flex align-items-center justify-content-center text-muted small mb-2"
             style="height: 150px; overflow: hidden;" data-image-box>
            <img alt="รูปใบขับขี่" class="d-none" style="max-height: 100%; max-width: 100%; object-fit: contain;" data-image-preview>
            <span data-image-empty>ยังไม่มีรูป</span>
        </div>
        <input type="file" id="{{ $p }}citizen_image" name="citizen_image" accept="image/jpeg,image/png,image/webp"
               class="form-control form-control-sm {{ $bad('citizen_image') }}" data-image-input>
        <div class="form-text">JPG / PNG / WEBP ไม่เกิน 2MB</div>
    </div>

    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="{{ $p }}fname_driver">ชื่อ <span class="req-star">*</span></label>
                <input type="text" id="{{ $p }}fname_driver" name="fname_driver" maxlength="255"
                       value="{{ $val('fname_driver') }}" class="form-control {{ $bad('fname_driver') }}"
                       placeholder="เช่น สมชาย" required data-required-msg="กรุณาใส่ชื่อ">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $p }}lname_driver">นามสกุล <span class="req-star">*</span></label>
                <input type="text" id="{{ $p }}lname_driver" name="lname_driver" maxlength="255"
                       value="{{ $val('lname_driver') }}" class="form-control {{ $bad('lname_driver') }}"
                       placeholder="เช่น ใจดี" required data-required-msg="กรุณาใส่นามสกุล">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $p }}phone_driver">เบอร์โทร (10 หลัก)</label>
                <input type="text" id="{{ $p }}phone_driver" name="phone_driver" maxlength="10" inputmode="numeric"
                       data-digits-only pattern="[0-9]{10}" data-pattern-msg="เบอร์โทรต้องเป็นตัวเลข 10 หลัก"
                       value="{{ $val('phone_driver') }}" class="form-control {{ $bad('phone_driver') }}"
                       placeholder="เช่น 0812345678">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="{{ $p }}citizenid_driver">เลขบัตรประชาชน (13 หลัก)</label>
                <input type="text" id="{{ $p }}citizenid_driver" name="citizenid_driver" maxlength="13" inputmode="numeric"
                       data-digits-only pattern="[0-9]{13}" data-pattern-msg="เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก"
                       value="{{ $val('citizenid_driver') }}" class="form-control {{ $bad('citizenid_driver') }}"
                       placeholder="เช่น 1234567890123">
            </div>
        </div>
    </div>
</div>

<h6 class="modal-section mt-4"><i class="bi bi-geo-alt me-1"></i>ที่อยู่</h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label" for="{{ $p }}address_no">บ้านเลขที่</label>
        <input type="text" id="{{ $p }}address_no" name="address_no" maxlength="50"
               value="{{ $val('address_no') }}" class="form-control {{ $bad('address_no') }}" placeholder="เช่น 123/45">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="{{ $p }}moo">หมู่ที่</label>
        <input type="text" id="{{ $p }}moo" name="moo" maxlength="20"
               value="{{ $val('moo') }}" class="form-control {{ $bad('moo') }}" placeholder="เช่น 5">
    </div>
    <div class="col-md-7">
        <label class="form-label" for="{{ $p }}address_detail">ซอย / ถนน / อาคาร</label>
        <input type="text" id="{{ $p }}address_detail" name="address_detail" maxlength="255"
               value="{{ $val('address_detail') }}" class="form-control {{ $bad('address_detail') }}"
               placeholder="เช่น ซอยสุขสมบูรณ์ ถนนมิตรภาพ">
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="{{ $p }}province">จังหวัด</label>
        <select id="{{ $p }}province" name="province" class="form-select" data-addr="province" data-value="{{ $val('province') }}">
            <option value="">— เลือกจังหวัด —</option>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="{{ $p }}district">อำเภอ / เขต</label>
        <select id="{{ $p }}district" name="district" class="form-select" data-addr="district" data-value="{{ $val('district') }}" disabled>
            <option value="">— เลือกอำเภอ —</option>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="{{ $p }}subdistrict">ตำบล / แขวง</label>
        <select id="{{ $p }}subdistrict" name="subdistrict" class="form-select" data-addr="subdistrict" data-value="{{ $val('subdistrict') }}" disabled>
            <option value="">— เลือกตำบล —</option>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="{{ $p }}zipcode">รหัสไปรษณีย์</label>
        <input type="text" id="{{ $p }}zipcode" name="zipcode" maxlength="5" inputmode="numeric" data-digits-only
               pattern="[0-9]{5}" data-pattern-msg="รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก"
               value="{{ $val('zipcode') }}" class="form-control {{ $bad('zipcode') }}" data-addr="zipcode"
               placeholder="เติมให้เมื่อเลือกตำบล">
    </div>
</div>
