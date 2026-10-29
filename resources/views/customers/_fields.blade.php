{{--
    ช่องกรอกข้อมูลลูกค้า — ใช้ทั้งหน้าต่างเพิ่มและหน้าต่างแก้ไข (วางไว้ "ข้างใน <form>")

    @include('customers._fields', [
        'p'   => 'c_',                 // คำนำหน้า id ของช่อง (กันชนกันเมื่อมีสองฟอร์มในหน้าเดียว)
        'v'   => [...],                // ค่าเริ่มต้น (หน้าต่างแก้ไขส่ง [] แล้วให้สคริปต์เติมค่า)
        'bag' => $errors->createCustomer,
    ])

    ช่องของประเภทที่ไม่ได้เลือกจะถูกซ่อน + disabled (ไม่ถูกส่งไปบันทึก) — ดูสคริปต์ใน customers/index
--}}
@php
    $v    = $v ?? [];
    $bag  = $bag ?? new \Illuminate\Support\MessageBag();
    $val  = fn (string $key, $default = '') => $v[$key] ?? $default;
    $bad  = fn (string $key) => $bag->has($key) ? 'is-invalid' : '';
    $type = $val('customer_type', 'person');
    $prefixes = \App\Models\Customer::PREFIXES;
@endphp

{{-- ---------- ประเภทลูกค้า ---------- --}}
<div class="mb-3">
    <div class="form-label fw-semibold mb-2">ประเภทลูกค้า</div>
    <div class="btn-group w-100" role="group" aria-label="ประเภทลูกค้า">
        <input type="radio" class="btn-check" name="customer_type" value="person" id="{{ $p }}type_person"
               autocomplete="off" data-customer-type @checked($type === 'person')>
        <label class="btn btn-outline-dark" for="{{ $p }}type_person">
            <i class="bi bi-person me-1"></i>บุคคลธรรมดา
        </label>

        <input type="radio" class="btn-check" name="customer_type" value="company" id="{{ $p }}type_company"
               autocomplete="off" data-customer-type @checked($type === 'company')>
        <label class="btn btn-outline-dark" for="{{ $p }}type_company">
            <i class="bi bi-building me-1"></i>บริษัท / นิติบุคคล
        </label>
    </div>
</div>

{{-- ---------- ข้อมูลลูกค้า ---------- --}}
<h6 class="customer-section"><i class="bi bi-person-vcard me-1"></i>ข้อมูลลูกค้า</h6>

{{-- บุคคล --}}
<div class="row g-3 mb-3" data-type-block="person">
    <div class="col-md-3">
        <label class="form-label" for="{{ $p }}name_prefix">คำนำหน้า</label>
        <select id="{{ $p }}name_prefix" name="name_prefix" class="form-select {{ $bad('name_prefix') }}">
            <option value="">— ไม่ระบุ —</option>
            @foreach ($prefixes as $prefix)
                <option value="{{ $prefix }}" @selected($val('name_prefix') === $prefix)>{{ $prefix }}</option>
            @endforeach
            @if ($val('name_prefix') && ! in_array($val('name_prefix'), $prefixes, true))
                <option value="{{ $val('name_prefix') }}" selected>{{ $val('name_prefix') }}</option>
            @endif
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $p }}first_name">ชื่อ <span class="req-star">*</span></label>
        <input type="text" id="{{ $p }}first_name" name="first_name" maxlength="100"
               value="{{ $val('first_name') }}" class="form-control {{ $bad('first_name') }}"
               placeholder="เช่น สมชาย" required data-required-msg="กรุณาใส่ชื่อลูกค้า">
    </div>
    <div class="col-md-5">
        <label class="form-label" for="{{ $p }}last_name">นามสกุล</label>
        <input type="text" id="{{ $p }}last_name" name="last_name" maxlength="100"
               value="{{ $val('last_name') }}" class="form-control {{ $bad('last_name') }}"
               placeholder="เช่น ใจดี">
    </div>
</div>

{{-- บริษัท --}}
<div class="row g-3 mb-3" data-type-block="company">
    <div class="col-12">
        <label class="form-label" for="{{ $p }}name_customer">ชื่อบริษัท / นิติบุคคล <span class="req-star">*</span></label>
        <input type="text" id="{{ $p }}name_customer" name="name_customer" maxlength="255"
               value="{{ $val('name_customer') }}" class="form-control {{ $bad('name_customer') }}"
               placeholder="เช่น บริษัท ทูแฟมิลี่ เอ็นจิเนียริ่ง จำกัด" required data-required-msg="กรุณาใส่ชื่อบริษัท">
        <div class="form-text">พิมพ์ชื่อเต็มตามหนังสือรับรองบริษัท (ใช้พิมพ์ในใบแจ้งหนี้ / ใบเสร็จ)</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $p }}branch_kind">สำนักงาน</label>
        <select id="{{ $p }}branch_kind" name="branch_kind" class="form-select" data-branch-kind>
            <option value="head" @selected($val('branch_kind', 'head') === 'head')>สำนักงานใหญ่</option>
            <option value="branch" @selected($val('branch_kind') === 'branch')>สาขา</option>
        </select>
    </div>
    <div class="col-md-3" data-branch-no-box>
        <label class="form-label" for="{{ $p }}branch_no">เลขที่สาขา <span class="req-star">*</span></label>
        <input type="text" id="{{ $p }}branch_no" name="branch_no" maxlength="5" inputmode="numeric" data-digits-only
               value="{{ $val('branch_no') }}" class="form-control {{ $bad('branch_no') }}"
               placeholder="เช่น 00001" required data-required-msg="กรุณาใส่เลขที่สาขา">
    </div>
    <div class="col">
        <label class="form-label" for="{{ $p }}contact_name">ชื่อผู้ติดต่อ</label>
        <input type="text" id="{{ $p }}contact_name" name="contact_name" maxlength="150"
               value="{{ $val('contact_name') }}" class="form-control {{ $bad('contact_name') }}"
               placeholder="เช่น คุณสมศักดิ์ (ฝ่ายจัดซื้อ)">
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label" for="{{ $p }}tax_id">เลขประจำตัวผู้เสียภาษี (13 หลัก)</label>
        <input type="text" id="{{ $p }}tax_id" name="tax_id" maxlength="13" inputmode="numeric" data-digits-only
               pattern="[0-9]{13}" data-pattern-msg="กรุณาใส่เลขประจำตัวผู้เสียภาษีเป็นตัวเลข 13 หลัก"
               value="{{ $val('tax_id') }}" class="form-control {{ $bad('tax_id') }}" placeholder="เช่น 0105512345678">
        <div class="form-text">
            <span data-type-block="person">บุคคลธรรมดาใช้เลขบัตรประชาชน</span>
            <span data-type-block="company">ใช้เลขทะเบียนนิติบุคคล</span>
            — ไม่บังคับ แต่ต้องมีหากลูกค้าต้องการใบกำกับภาษีเต็มรูป
        </div>
    </div>
</div>

{{-- ---------- ช่องทางติดต่อ ---------- --}}
<h6 class="customer-section"><i class="bi bi-telephone me-1"></i>ช่องทางติดต่อ</h6>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label" for="{{ $p }}phone_customer">เบอร์โทร (10 หลัก)</label>
        <input type="text" id="{{ $p }}phone_customer" name="phone_customer" maxlength="10" inputmode="numeric"
               data-digits-only pattern="[0-9]{10}" data-pattern-msg="กรุณาใส่เบอร์โทรเป็นตัวเลข 10 หลัก"
               value="{{ $val('phone_customer') }}" class="form-control {{ $bad('phone_customer') }}"
               placeholder="เช่น 0812345678">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $p }}email_customer">อีเมล</label>
        <input type="email" id="{{ $p }}email_customer" name="email_customer" maxlength="255"
               value="{{ $val('email_customer') }}" class="form-control {{ $bad('email_customer') }}"
               placeholder="เช่น example@email.com">
    </div>
</div>

{{-- ---------- ที่อยู่ ---------- --}}
<h6 class="customer-section"><i class="bi bi-geo-alt me-1"></i>ที่อยู่ (สำหรับออกเอกสาร)</h6>
<div class="row g-3" data-address>
    <div class="col-md-3">
        <label class="form-label" for="{{ $p }}address_no">บ้านเลขที่</label>
        <input type="text" id="{{ $p }}address_no" name="address_no" maxlength="30"
               value="{{ $val('address_no') }}" class="form-control {{ $bad('address_no') }}" placeholder="เช่น 99/9">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="{{ $p }}moo">หมู่ที่</label>
        <input type="text" id="{{ $p }}moo" name="moo" maxlength="10"
               value="{{ $val('moo') }}" class="form-control {{ $bad('moo') }}" placeholder="เช่น 5">
    </div>
    <div class="col-md-7">
        <label class="form-label" for="{{ $p }}address_detail">หมู่บ้าน / อาคาร / ชั้น</label>
        <input type="text" id="{{ $p }}address_detail" name="address_detail" maxlength="255"
               value="{{ $val('address_detail') }}" class="form-control {{ $bad('address_detail') }}"
               placeholder="เช่น หมู่บ้านสุขใจ หรือ อาคารเอ ชั้น 3">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $p }}soi">ซอย</label>
        <input type="text" id="{{ $p }}soi" name="soi" maxlength="100"
               value="{{ $val('soi') }}" class="form-control {{ $bad('soi') }}" placeholder="เช่น ร่มเย็น 3">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $p }}road">ถนน</label>
        <input type="text" id="{{ $p }}road" name="road" maxlength="100"
               value="{{ $val('road') }}" class="form-control {{ $bad('road') }}" placeholder="เช่น มิตรภาพ">
    </div>

    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="{{ $p }}province">จังหวัด</label>
        <select id="{{ $p }}province" name="province" class="form-select {{ $bad('province') }}"
                data-addr="province" data-value="{{ $val('province') }}">
            <option value="">— เลือกจังหวัด —</option>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="{{ $p }}district">อำเภอ / เขต</label>
        <select id="{{ $p }}district" name="district" class="form-select {{ $bad('district') }}"
                data-addr="district" data-value="{{ $val('district') }}" disabled>
            <option value="">— เลือกอำเภอ —</option>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="{{ $p }}subdistrict">ตำบล / แขวง</label>
        <select id="{{ $p }}subdistrict" name="subdistrict" class="form-select {{ $bad('subdistrict') }}"
                data-addr="subdistrict" data-value="{{ $val('subdistrict') }}" disabled>
            <option value="">— เลือกตำบล —</option>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="{{ $p }}zipcode">รหัสไปรษณีย์</label>
        <input type="text" id="{{ $p }}zipcode" name="zipcode" maxlength="5" inputmode="numeric" data-digits-only
               pattern="[0-9]{5}" data-pattern-msg="รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก"
               value="{{ $val('zipcode') }}" class="form-control {{ $bad('zipcode') }}"
               data-addr="zipcode" placeholder="เติมให้เมื่อเลือกตำบล">
    </div>
</div>
