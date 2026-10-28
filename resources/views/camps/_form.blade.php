@extends('layout')

@section('namepage')
    <div class="container">
        <h3>{{ $isEdit ? 'แก้ไขข้อมูลแคมป์' : 'เพิ่มข้อมูลแคมป์' }}</h3>
    </div>
@endsection

@section('content')
    <style>
        #map {
            width: 100%;
            height: 600px;
            border-radius: 10px;
            background: #e9ecef;
        }

        .map-panel {
            position: sticky;
            top: 1rem;
        }

        .pac-container {
            z-index: 99999 !important;
            pointer-events: auto !important;
        }

        .pac-item {
            min-height: 48px;
            padding: 10px 12px !important;
            cursor: pointer !important;
            pointer-events: auto !important;
        }

        .pac-item:hover,
        .pac-item-selected {
            background-color: #eef5ff !important;
        }

        /* แถบปุ่มบันทึกติดขอบล่าง กดได้ตลอดไม่ต้องเลื่อนลงสุดฟอร์ม */
        .form-actions {
            position: sticky;
            bottom: 0;
            z-index: 10;
            background: rgba(255, 255, 255, .96);
            border-top: 1px solid #dee2e6;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, .04);
            margin: 1.5rem -.75rem 0;
            padding: .75rem;
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background: #212529;
            color: #fff;
            font-size: .8rem;
            margin-right: .4rem;
        }

        @media (max-width: 991.98px) {
            .map-panel {
                position: static;
            }

            #map {
                height: 450px;
            }
        }
    </style>

    <div class="container py-3">
        {{-- ปุ่มกลับ แบบเดียวกับหน้าดูแคมป์ --}}
        <a href="{{ $isEdit ? route('camps.show', $camp) : route('camps.index') }}"
           class="btn btn-sm btn-light mb-3">
            <i class="bi bi-arrow-left me-1"></i>{{ $isEdit ? 'กลับหน้าแคมป์' : 'กลับหน้ารายการ' }}
        </a>

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">
                    กรุณาตรวจสอบข้อมูล
                </div>

                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            id="campForm"
            method="POST"
            action="{{ $isEdit
                ? route('camps.update', ['camp' => $camp->getRouteKey()])
                : route('camps.store') }}"
            data-confirm="{{ $isEdit ? 'ต้องการบันทึกการแก้ไขข้อมูลแคมป์นี้หรือไม่' : 'ต้องการเพิ่มแคมป์งานนี้หรือไม่' }}" data-confirm-title="{{ $isEdit ? 'ยืนยันการแก้ไขข้อมูล' : 'ยืนยันการบันทึกข้อมูล' }}" data-confirm-ok="บันทึก" data-confirm-variant="primary">
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            {{-- เปิดแคมป์ใหม่จากใบสั่งขาย: บันทึกแล้วระบบจะผูกใบสั่งขายเข้าแคมป์นี้ให้ --}}
            @if (! empty($salesOrder))
                <input type="hidden" name="id_so" value="{{ $salesOrder->id_so }}">
                <div class="alert alert-primary d-flex align-items-center gap-2">
                    <i class="bi bi-link-45deg fs-5"></i>
                    <div>
                        เปิดแคมป์ใหม่จากใบสั่งขาย <strong>{{ $salesOrder->code_so }}</strong>
                        ({{ $salesOrder->customer->name_customer ?? '-' }}
                        · {{ number_format($salesOrder->total_amount, 2) }} บาท)
                        <div class="small">บันทึกแล้วใบสั่งขายจะอยู่ในแคมป์นี้ ถ้าลูกค้าสั่งเพิ่มภายหลัง เปิดใบสั่งขายใหม่แล้วกด "เพิ่มเข้าแคมป์เดิม"</div>
                    </div>
                </div>
            @endif

            {{-- เปิดแคมป์ใหม่ (ยังไม่ได้เลือกใบสั่งขาย): แนะนำให้เปิดจากใบสั่งขาย ระบบจะเติมลูกค้าและผูกสัญญาให้ --}}
            @if (! $isEdit && empty($salesOrder) && ($openableOrders ?? collect())->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 d-flex flex-wrap align-items-center gap-2">
                        <i class="bi bi-lightning-charge text-warning fs-5"></i>
                        <span class="fw-semibold">เปิดแคมป์จากใบสั่งขาย</span>
                        <span class="text-muted small">— ระบบเติมลูกค้าและผูกรายการตามสัญญาให้</span>
                        <select class="form-select form-select-sm w-auto ms-lg-auto" id="pick_sales_order">
                            <option value="">— เลือกใบสั่งขายที่ยืนยันแล้ว ({{ $openableOrders->count() }}) —</option>
                            @foreach ($openableOrders as $so)
                                <option value="{{ route('camps.create', ['sales_order' => $so->id_so]) }}">
                                    {{ $so->code_so }} · {{ $so->customer->name_customer ?? '-' }}
                                    ({{ number_format($so->total_amount, 2) }} บาท)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-6 d-flex flex-column gap-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">
                                <span class="step-badge">1</span>ข้อมูลแคมป์
                            </h6>

                            <div class="mb-3">
                                <label for="id_quot" class="form-label">
                                    อ้างอิงใบเสนอราคา
                                    <span class="text-muted fw-normal">
                                        (ไม่บังคับ)
                                    </span>
                                </label>

                                <select
                                    id="id_quot"
                                    name="id_quot"
                                    class="form-select @error('id_quot') is-invalid @enderror"
                                >
                                    <option value="">
                                        — ไม่อ้างอิงใบเสนอราคา —
                                    </option>

                                    @foreach ($quotations ?? [] as $quotation)
                                        <option
                                            value="{{ $quotation->id_quot }}"
                                            @selected(old('id_quot', $camp->id_quot ?? '') == $quotation->id_quot)
                                        >
                                            {{ $quotation->code_quot }}
                                            — {{ $quotation->customer->name_customer ?? '-' }}
                                            ({{ number_format($quotation->total_amount, 2) }} บาท)
                                        </option>
                                    @endforeach
                                </select>

                                <div class="form-text">
                                    แสดงเฉพาะใบเสนอราคาที่อนุมัติแล้ว
                                    เมื่อเลือกแล้วระบบจะเติมลูกค้าให้อัตโนมัติ
                                </div>

                                @error('id_quot')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="id_customer" class="form-label">
                                    ลูกค้า
                                </label>

                                <select
                                    id="id_customer"
                                    name="id_customer"
                                    class="form-select @error('id_customer') is-invalid @enderror"
                                    required
                                    data-required-msg="กรุณาเลือกลูกค้า"
                                    data-searchable
                                    data-placeholder="พิมพ์ชื่อลูกค้าเพื่อค้นหา..."
                                >
                                    <option value="">— เลือกลูกค้า —</option>

                                    @foreach ($customers as $customer)
                                        <option
                                            value="{{ $customer->id_customer }}"
                                            @selected(old('id_customer', $camp->id_customer ?? '') == $customer->id_customer)
                                        >
                                            {{ $customer->name_customer }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('id_customer')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="name_camp" class="form-label">
                                    ชื่อแคมป์
                                </label>

                                <input
                                    type="text"
                                    id="name_camp"
                                    name="name_camp"
                                    value="{{ old('name_camp', $camp->name_camp ?? '') }}"
                                    class="form-control @error('name_camp') is-invalid @enderror"
                                    placeholder="เช่น แคมป์บ้านไผ่ เฟส 2"
                                    maxlength="255"
                                    required
                                    data-required-msg="กรุณาใส่ชื่อแคมป์"
                                >

                                @error('name_camp')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- แคมป์ใหม่เริ่มที่ "เตรียมงาน" เสมอ — เปลี่ยนสถานะจากแถบ "ทำงานต่อ" ในหน้าแคมป์ --}}
                            @if ($isEdit)
                            <div class="mb-3">
                                <label for="status_camp" class="form-label">
                                    สถานะ
                                </label>

                                @php
                                    $selectedStatus = old(
                                        'status_camp',
                                        $camp->status_camp ?? 'preparing'
                                    );
                                @endphp

                                <select
                                    id="status_camp"
                                    name="status_camp"
                                    class="form-select @error('status_camp') is-invalid @enderror"
                                    required
                                >
                                    @foreach (\App\Models\Camp::STATUS_LABELS as $value => $label)
                                        <option
                                            value="{{ $value }}"
                                            @selected($selectedStatus === $value)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('status_camp')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            @else
                                <input type="hidden" name="status_camp" value="preparing">
                                <div class="small text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    สถานะเริ่มต้น <span class="badge bg-info">เตรียมงาน</span>
                                    — เปลี่ยนเป็น "กำลังดำเนินงาน" ให้เองเมื่อออกใบส่งของใบแรก
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">
                                <span class="step-badge">2</span>ที่อยู่หน้างาน
                            </h6>

                            <div class="mb-3">
                                <label for="address_detail" class="form-label">
                                    บ้านเลขที่ / หมู่ / รายละเอียดที่อยู่
                                </label>

                                <input
                                    type="text"
                                    id="address_detail"
                                    name="address_detail"
                                    value="{{ old('address_detail', $camp->address_detail ?? '') }}"
                                    class="form-control @error('address_detail') is-invalid @enderror"
                                    placeholder="เช่น 123/45 หมู่ 6"
                                >

                                <div class="form-text">
                                    ระบบเติมข้อมูลที่หาได้จากแผนที่
                                    กรุณาตรวจสอบบ้านเลขที่และหมู่ก่อนบันทึก
                                </div>

                                @error('address_detail')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="province" class="form-label">
                                        จังหวัด
                                    </label>

                                    <select
                                        id="province"
                                        name="province"
                                        class="form-select @error('province') is-invalid @enderror"
                                        required
                                    >
                                        <option value="">— เลือกจังหวัด —</option>
                                    </select>

                                    @error('province')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="district" class="form-label">
                                        อำเภอ / เขต
                                    </label>

                                    <select
                                        id="district"
                                        name="district"
                                        class="form-select @error('district') is-invalid @enderror"
                                        disabled
                                        required
                                    >
                                        <option value="">— เลือกอำเภอ / เขต —</option>
                                    </select>

                                    @error('district')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="subdistrict" class="form-label">
                                        ตำบล / แขวง
                                    </label>

                                    <select
                                        id="subdistrict"
                                        name="subdistrict"
                                        class="form-select @error('subdistrict') is-invalid @enderror"
                                        disabled
                                        required
                                    >
                                        <option value="">— เลือกตำบล / แขวง —</option>
                                    </select>

                                    @error('subdistrict')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="zipcode" class="form-label">
                                        รหัสไปรษณีย์
                                    </label>

                                    <input
                                        type="text"
                                        id="zipcode"
                                        name="zipcode"
                                        value="{{ old('zipcode', $camp->zipcode ?? '') }}"
                                        class="form-control @error('zipcode') is-invalid @enderror"
                                        maxlength="5"
                                        inputmode="numeric"
                                        pattern="[0-9]{5}"
                                        placeholder="เช่น 40000"
                                    >

                                    @error('zipcode')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="latitude" class="form-label">
                                        ละติจูด
                                    </label>

                                    <input
                                        type="number"
                                        step="0.0000001"
                                        id="latitude"
                                        name="latitude"
                                        value="{{ old('latitude', $camp->latitude ?? '') }}"
                                        class="form-control bg-light @error('latitude') is-invalid @enderror"
                                        readonly
                                    >

                                    @error('latitude')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="longitude" class="form-label">
                                        ลองจิจูด
                                    </label>

                                    <input
                                        type="number"
                                        step="0.0000001"
                                        id="longitude"
                                        name="longitude"
                                        value="{{ old('longitude', $camp->longitude ?? '') }}"
                                        class="form-control bg-light @error('longitude') is-invalid @enderror"
                                        readonly
                                    >

                                    @error('longitude')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <div
                                id="address_notice"
                                class="alert alert-warning mt-3 d-none"
                                role="status"
                            ></div>

                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">
                                <span class="step-badge">3</span>ผู้ติดต่อหน้างาน
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="contact_name" class="form-label">
                                        ชื่อผู้ติดต่อหน้างาน
                                    </label>

                                    <input
                                        type="text"
                                        id="contact_name"
                                        name="contact_name"
                                        value="{{ old('contact_name', $camp->contact_name ?? '') }}"
                                        class="form-control @error('contact_name') is-invalid @enderror"
                                    >

                                    @error('contact_name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="contact_phone" class="form-label">
                                        เบอร์โทร (10 หลัก)
                                    </label>

                                    <input
                                        type="text"
                                        id="contact_phone"
                                        name="contact_phone"
                                        value="{{ old('contact_phone', $camp->contact_phone ?? '') }}"
                                        class="form-control @error('contact_phone') is-invalid @enderror"
                                        maxlength="10"
                                        pattern="[0-9]{10}"
                                        inputmode="numeric" data-digits-only
                                        placeholder="เช่น 0812345678"
                                    >

                                    @error('contact_phone')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="note" class="form-label">
                                        หมายเหตุ
                                    </label>

                                    <textarea
                                        id="note"
                                        name="note"
                                        rows="3"
                                        class="form-control @error('note') is-invalid @enderror"
                                    >{{ old('note', $camp->note ?? '') }}</textarea>

                                    @error('note')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm map-panel">
                        <div class="card-body p-4">
                            <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">
                                <i class="bi bi-geo-alt me-1"></i>ตำแหน่งหน้างานบนแผนที่
                            </h6>

                            <div class="mb-3">
                                <label for="place_search" class="form-label">
                                    ค้นหาสถานที่
                                </label>

                                <input
                                    type="text"
                                    id="place_search"
                                    class="form-control"
                                    placeholder="ค้นหาชื่อแคมป์ บริษัท หมู่บ้าน หรือสถานที่"
                                    autocomplete="off"
                                >

                                <div class="form-text">
                                    เลือกจากรายการที่ Google แนะนำ
                                    ระบบจะปักหมุดและเติมข้อมูลที่อยู่ให้อัตโนมัติ
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <button
                                    type="button"
                                    class="btn btn-outline-dark btn-sm"
                                    id="searchFromAddress"
                                >
                                    ค้นหาจากที่อยู่
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    id="useCurrentLocation"
                                >
                                    ใช้ตำแหน่งปัจจุบัน
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    id="clearPin"
                                >
                                    ล้างหมุด
                                </button>
                            </div>

                            <div id="map"></div>

                            <div class="form-text text-muted mt-2">
                                คลิกบนแผนที่หรือลากหมุดเพื่อเปลี่ยนตำแหน่ง
                                ข้อมูลที่อยู่จะปรับตามตำแหน่งใหม่
                            </div>

                            <div
                                id="map_status"
                                class="alert alert-info mt-3 d-none"
                                role="status"
                            ></div>

                            <div
                                id="map_error"
                                class="alert alert-danger mt-3 d-none"
                                role="alert"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions d-flex justify-content-end align-items-center gap-2">
                <span class="small text-muted me-auto d-none d-md-inline">
                    {{ $isEdit ? 'แก้ไขแคมป์ ' . $camp->code_camp : 'เลขที่แคมป์จะออกให้อัตโนมัติเมื่อบันทึก' }}
                </span>
                <a href="{{ $isEdit ? route('camps.show', $camp) : route('camps.index') }}"
                   class="btn btn-outline-secondary">
                    ยกเลิก
                </a>

                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกแคมป์' }}
                </button>
            </div>
        </form>

        {{-- เพิ่ม / ถอนรถประจำแคมป์ — ทำได้เฉพาะหน้าแก้ไข (หน้าดูข้อมูลเป็น read-only)
             อยู่นอก <form> หลัก เพราะแต่ละปุ่มส่งฟอร์มของตัวเอง --}}
        @if ($isEdit)
            @include('camps._trucks')
        @endif
    </div>

    <script>
        // เลือกใบสั่งขาย → เปิดหน้าสร้างแคมป์ใหม่ที่เติมข้อมูลจากใบสั่งขายนั้น
        document.getElementById('pick_sales_order')?.addEventListener('change', (e) => {
            if (e.target.value) window.location = e.target.value;
        });
    </script>

    <script>
        (() => {
            const thaiAddressApi =
                @json(asset('data/thai-address.json'));

            const defaultPosition = {
                lat: 16.4419,
                lng: 102.8360
            };

            const quotationMap = @json(
                ($quotations ?? collect())->mapWithKeys(fn($q) => [
                    $q->id_quot => ['id_customer' => $q->id_customer],
                ])
            );

            const oldProvince = @json(old('province', $camp->province ?? ''));
            const oldDistrict = @json(old('district', $camp->district ?? ''));
            const oldSubdistrict = @json(old('subdistrict', $camp->subdistrict ?? ''));
            const oldZipcode = @json(old('zipcode', $camp->zipcode ?? ''));

            const quotationSelect = document.getElementById('id_quot');
            const customerSelect = document.getElementById('id_customer');
            const provinceSelect = document.getElementById('province');
            const districtSelect = document.getElementById('district');
            const subdistrictSelect = document.getElementById('subdistrict');
            const zipcodeInput = document.getElementById('zipcode');
            const addressDetailInput = document.getElementById('address_detail');
            const placeSearchInput = document.getElementById('place_search');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');
            const searchAddressButton = document.getElementById('searchFromAddress');
            const clearPinButton = document.getElementById('clearPin');
            const currentLocationButton = document.getElementById('useCurrentLocation');
            const statusElement = document.getElementById('map_status');
            const errorElement = document.getElementById('map_error');
            const addressNotice = document.getElementById('address_notice');

            let thaiData = [];
            let addressDataPromise = null;
            let restoredOldAddress = false;

            let map = null;
            let marker = null;
            let geocoder = null;
            let placesService = null;
            let autocomplete = null;

            // ป้องกันผลค้นหาครั้งเก่าทับตำแหน่งที่เลือกใหม่
            let requestVersion = 0;

            quotationSelect.addEventListener('change', function () {
                const info = quotationMap[this.value];
                if (!info) return;
                customerSelect.value = String(info.id_customer);
                // แจ้งช่องค้นหาลูกค้า (searchable-select) ให้แสดงชื่อที่เลือกด้วย
                customerSelect.dispatchEvent(new Event('change', { bubbles: true }));
            });

            /* ---------- แจ้งเตือน ---------- */

            function showStatus(message) {
                errorElement.textContent = '';
                errorElement.classList.add('d-none');

                statusElement.textContent = message;
                statusElement.classList.remove('d-none');
            }

            function showError(message) {
                statusElement.textContent = '';
                statusElement.classList.add('d-none');

                errorElement.textContent = message;
                errorElement.classList.remove('d-none');
            }

            function clearMessages() {
                statusElement.textContent = '';
                errorElement.textContent = '';

                statusElement.classList.add('d-none');
                errorElement.classList.add('d-none');
            }

            function showAddressNotice(message = '') {
                addressNotice.textContent = message;
                addressNotice.classList.toggle('d-none', !message);
            }

            /* ---------- ข้อมูลจังหวัด อำเภอ ตำบล ---------- */

            function normalizeThaiAddress(value) {
                const name = String(value ?? '')
                    .normalize('NFC')
                    .trim()
                    .replace(
                        /^(?:จังหวัด|จ\.|อำเภอ|อ\.|เขต|ตำบล|ต\.|แขวง)\s*/,
                        ''
                    )
                    .replace(/\s+/g, '');

                if ([
                    'กรุงเทพ',
                    'กรุงเทพฯ',
                    'กรุงเทพมหานคร',
                    'Bangkok'
                ].includes(name)) {
                    return 'กรุงเทพมหานคร';
                }

                return name;
            }

            function getDistricts(province) {
                const items =
                    province?.district ??
                    province?.districts ??
                    province?.amphure ??
                    [];

                return Array.isArray(items) ? items : [];
            }

            function getSubdistricts(district) {
                const items =
                    district?.sub_district ??
                    district?.sub_districts ??
                    district?.subdistrict ??
                    district?.subdistricts ??
                    district?.tambon ??
                    [];

                return Array.isArray(items) ? items : [];
            }

            function getName(item) {
                const name = item?.name_th
                    ?? (
                        typeof item?.name === 'object'
                            ? item?.name?.th
                            : item?.name
                    )
                    ?? '';

                return String(name).trim();
            }

            function getZipcode(item) {
                return String(
                    item?.zip_code ??
                    item?.zipcode ??
                    item?.postal_code ??
                    ''
                );
            }

            function findProvince(value) {
                const target = normalizeThaiAddress(value);

                if (!target) return null;

                return thaiData.find(item =>
                    normalizeThaiAddress(getName(item)) === target
                ) || null;
            }

            function findOption(select, values) {
                const candidates = Array.isArray(values) ? values : [values];

                for (const value of candidates) {
                    const target = normalizeThaiAddress(value);

                    if (!target) continue;

                    const option = Array.from(select.options).find(item =>
                        item.value &&
                        normalizeThaiAddress(item.value) === target
                    );

                    if (option) return option;
                }

                return null;
            }

            function selectMatchingOption(select, values) {
                const option = findOption(select, values);

                if (!option) return false;

                select.value = option.value;
                select.dispatchEvent(new Event('change'));

                return true;
            }

            function resetSelect(select, placeholder) {
                select.replaceChildren(new Option(placeholder, ''));
                select.disabled = true;
            }

            function populateDistricts(provinceName) {
                resetSelect(districtSelect, '— เลือกอำเภอ / เขต —');
                resetSelect(subdistrictSelect, '— เลือกตำบล / แขวง —');
                zipcodeInput.value = '';

                const province = findProvince(provinceName);

                if (!province) return;

                for (const district of getDistricts(province)) {
                    const name = getName(district);

                    if (name) {
                        districtSelect.add(new Option(name, name));
                    }
                }

                districtSelect.disabled = districtSelect.options.length <= 1;
            }

            function populateSubdistricts(provinceName, districtName) {
                resetSelect(subdistrictSelect, '— เลือกตำบล / แขวง —');
                zipcodeInput.value = '';

                const province = findProvince(provinceName);

                if (!province) return;

                const district = getDistricts(province).find(item =>
                    normalizeThaiAddress(getName(item)) ===
                    normalizeThaiAddress(districtName)
                );

                if (!district) return;

                for (const subdistrict of getSubdistricts(district)) {
                    const name = getName(subdistrict);

                    if (!name) continue;

                    const option = new Option(name, name);
                    option.dataset.zip = getZipcode(subdistrict);

                    subdistrictSelect.add(option);
                }

                subdistrictSelect.disabled =
                    subdistrictSelect.options.length <= 1;
            }

            provinceSelect.addEventListener('change', function () {
                populateDistricts(this.value);
            });

            districtSelect.addEventListener('change', function () {
                populateSubdistricts(provinceSelect.value, this.value);
            });

            subdistrictSelect.addEventListener('change', function () {
                zipcodeInput.value =
                    this.selectedOptions[0]?.dataset.zip || '';
            });

            async function loadThaiAddressData() {
                if (addressDataPromise) return addressDataPromise;

                addressDataPromise = (async () => {
                    const controller = new AbortController();
                    const timer = setTimeout(() => controller.abort(), 15000);

                    let payload;

                    try {
                        const response = await fetch(thaiAddressApi, {
                            signal: controller.signal
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        payload = await response.json();
                    } finally {
                        clearTimeout(timer);
                    }

                    const rows = Array.isArray(payload)
                        ? payload
                        : payload?.data;

                    if (!Array.isArray(rows) || !rows.length) {
                        throw new Error('รูปแบบข้อมูลจังหวัดไม่ถูกต้อง');
                    }

                    if (!rows.some(item => getDistricts(item).length)) {
                        throw new Error('ไม่พบข้อมูลอำเภอในชุดข้อมูล');
                    }

                    thaiData = rows;

                    provinceSelect.replaceChildren(
                        new Option('— เลือกจังหวัด —', '')
                    );

                    for (const province of thaiData) {
                        const name = getName(province);

                        if (name) {
                            provinceSelect.add(new Option(name, name));
                        }
                    }

                    provinceSelect.disabled = false;

                    // คืนค่าเดิมเฉพาะการโหลดครั้งแรก
                    if (!restoredOldAddress) {
                        restoredOldAddress = true;

                        if (selectMatchingOption(provinceSelect, oldProvince)) {
                            if (selectMatchingOption(districtSelect, oldDistrict)) {
                                selectMatchingOption(
                                    subdistrictSelect,
                                    oldSubdistrict
                                );
                            }
                        }

                        if (oldZipcode !== null && oldZipcode !== '') {
                            zipcodeInput.value = String(oldZipcode);
                        }
                    }
                })();

                try {
                    await addressDataPromise;
                } catch (error) {
                    addressDataPromise = null;
                    throw error;
                }
            }

            /* ---------- แยกข้อมูลที่อยู่จาก Google ---------- */

            function componentNames(components, types) {
                const names = [];

                // ลำดับ types คือความสำคัญในการจับคู่
                for (const type of types) {
                    for (const component of components) {
                        if (!component.types?.includes(type)) continue;

                        if (component.long_name) {
                            names.push(component.long_name);
                        }

                        if (component.short_name) {
                            names.push(component.short_name);
                        }
                    }
                }

                return [...new Set(names)];
            }

            function firstComponentName(components, type) {
                return components.find(component =>
                    component.types?.includes(type)
                )?.long_name || '';
            }

            function extractThaiAddress(components, formattedAddress = '') {
                const provinces = componentNames(components, [
                    'administrative_area_level_1'
                ]);

                const isBangkok = provinces.some(name =>
                    normalizeThaiAddress(name) === 'กรุงเทพมหานคร'
                );

                const districts = componentNames(
                    components,
                    isBangkok
                        ? [
                            'sublocality_level_1',
                            'administrative_area_level_2',
                            'locality'
                        ]
                        : [
                            'administrative_area_level_2',
                            'locality',
                            'sublocality_level_1'
                        ]
                );

                const subdistricts = componentNames(
                    components,
                    isBangkok
                        ? [
                            'sublocality_level_2',
                            'administrative_area_level_3',
                            'sublocality'
                        ]
                        : [
                            'sublocality_level_1',
                            'administrative_area_level_3',
                            'sublocality_level_2',
                            'sublocality',
                            'locality'
                        ]
                );

                const streetNumber =
                    firstComponentName(components, 'street_number');

                const route =
                    firstComponentName(components, 'route');

                let detail = '';

                // ใช้ส่วนต้นของที่อยู่ก่อนตำบล/อำเภอ/จังหวัด
                // เพื่อเก็บบ้านเลขที่และหมู่ หาก Google ส่งมาใน formatted_address
                const boundaries = [
                    ...subdistricts,
                    ...districts,
                    ...provinces
                ].filter(name => /[ก-๙]/.test(name));

                const positions = boundaries
                    .map(name => formattedAddress.indexOf(name))
                    .filter(index => index >= 0);

                if (positions.length) {
                    detail = formattedAddress
                        .slice(0, Math.min(...positions))
                        .replace(/(?:ตำบล|ต\.|แขวง|อำเภอ|อ\.|เขต|จังหวัด|จ\.)\s*$/, '')
                        .replace(/[\s,]+$/, '')
                        .trim();

                    // ไม่ใช้ Plus Code เป็นบ้านเลขที่
                    if (/^[A-Z0-9]{4,8}\+[A-Z0-9]{2,3}(?:\s|$)/i.test(detail)) {
                        detail = '';
                    }
                }

                if (!detail) {
                    detail = [streetNumber, route]
                        .filter(Boolean)
                        .join(' ');
                }

                return {
                    provinces,
                    districts,
                    subdistricts,
                    detail,
                    zipcode: firstComponentName(components, 'postal_code')
                };
            }

            async function applyAddressToFields(
                components,
                formattedAddress,
                token,
                preserveDetail = false
            ) {
                const address = extractThaiAddress(
                    components,
                    formattedAddress
                );

                try {
                    await loadThaiAddressData();
                } catch (error) {
                    if (token !== requestVersion) return;

                    if (!preserveDetail) {
                        addressDetailInput.value = address.detail;
                    }

                    // ล้างเขตการปกครองเดิมเพื่อไม่ให้ค้างจากหมุดก่อนหน้า
                    provinceSelect.value = '';
                    populateDistricts('');
                    zipcodeInput.value = address.zipcode;

                    showAddressNotice(
                        'โหลดรายการจังหวัด อำเภอ และตำบลไม่สำเร็จ ' +
                        'กรุณาตรวจสอบการเชื่อมต่อแล้วเลือกตำแหน่งอีกครั้ง'
                    );

                    console.error('Thai address data error:', error);
                    return;
                }

                if (token !== requestVersion) return;

                if (!preserveDetail) {
                    addressDetailInput.value = address.detail;
                }

                provinceSelect.value = '';
                populateDistricts('');

                const provinceMatched = selectMatchingOption(
                    provinceSelect,
                    address.provinces
                );

                const districtMatched = provinceMatched &&
                    selectMatchingOption(
                        districtSelect,
                        address.districts
                    );

                const subdistrictMatched = districtMatched &&
                    selectMatchingOption(
                        subdistrictSelect,
                        address.subdistricts
                    );

                // ใช้รหัสจากตำบลก่อน หากไม่มีจึงใช้รหัสที่ Google ส่งมา
                if (!zipcodeInput.value && address.zipcode) {
                    zipcodeInput.value = address.zipcode;
                }

                const missing = [];

                if (!provinceMatched) missing.push('จังหวัด');
                if (!districtMatched) missing.push('อำเภอ / เขต');
                if (!subdistrictMatched) missing.push('ตำบล / แขวง');

                if (missing.length) {
                    showAddressNotice(
                        `ยังจับคู่ข้อมูล ${missing.join(', ')} ไม่ได้ ` +
                        'กรุณาเลือกข้อมูลส่วนที่เหลือเอง'
                    );
                } else if (!address.detail && !preserveDetail) {
                    showAddressNotice(
                        'เติมจังหวัด อำเภอ และตำบลแล้ว ' +
                        'แต่ไม่พบรายละเอียดบ้านเลขที่หรือถนน กรุณากรอกเพิ่มเติม'
                    );
                } else {
                    showAddressNotice('');
                }
            }

            /* ---------- หมุดและพิกัด ---------- */

            function positionLat(position) {
                return typeof position.lat === 'function'
                    ? position.lat()
                    : position.lat;
            }

            function positionLng(position) {
                return typeof position.lng === 'function'
                    ? position.lng()
                    : position.lng;
            }

            function updateCoordinates(position) {
                latitudeInput.value =
                    Number(positionLat(position)).toFixed(7);

                longitudeInput.value =
                    Number(positionLng(position)).toFixed(7);
            }

            function setMarker(position, zoom = null) {
                if (!map) return;

                if (!marker) {
                    marker = new google.maps.Marker({
                        map,
                        position,
                        draggable: true,
                        title: 'ตำแหน่งแคมป์'
                    });

                    marker.addListener('dragstart', () => {
                        requestVersion++;
                    });

                    marker.addListener('dragend', () => {
                        const position = marker.getPosition();
                        const token = ++requestVersion;

                        updateCoordinates(position);
                        reverseGeocode(position, token);
                    });
                } else {
                    marker.setPosition(position);
                }

                updateCoordinates(position);
                map.panTo(position);

                if (zoom !== null) {
                    map.setZoom(zoom);
                }
            }

            function clearAddressForNewPosition() {
                addressDetailInput.value = '';
                provinceSelect.value = '';
                populateDistricts('');
                showAddressNotice('');
            }

            async function reverseGeocode(position, token) {
                if (!geocoder) return;

                clearAddressForNewPosition();
                placeSearchInput.value = '';
                showStatus('กำลังอ่านข้อมูลที่อยู่...');

                try {
                    const response = await geocoder.geocode({
                        location: position,
                        language: 'th',
                        region: 'TH'
                    });

                    if (token !== requestVersion) return;

                    const result = response.results?.[0];

                    if (!result) {
                        showAddressNotice(
                            'ไม่พบข้อมูลที่อยู่ของตำแหน่งนี้ กรุณากรอกเอง'
                        );
                        showStatus('ปักหมุดแล้ว แต่ไม่พบข้อมูลที่อยู่');
                        return;
                    }

                    placeSearchInput.value =
                        result.formatted_address || '';

                    await applyAddressToFields(
                        result.address_components || [],
                        result.formatted_address || '',
                        token
                    );

                    if (token !== requestVersion) return;

                    showStatus(
                        'ปักหมุดแล้ว และเติมข้อมูลที่อยู่เท่าที่พบ ' +
                        'กรุณาตรวจสอบก่อนบันทึก'
                    );
                } catch (error) {
                    if (token !== requestVersion) return;

                    showAddressNotice(
                        'อ่านข้อมูลที่อยู่ไม่สำเร็จ กรุณากรอกข้อมูลที่อยู่เอง'
                    );

                    showError(
                        'ปักหมุดแล้ว แต่ไม่สามารถอ่านข้อมูลที่อยู่ได้'
                    );

                    console.error('Reverse geocoding error:', error);
                }
            }

            async function selectGooglePlace(place, token) {
                if (token !== requestVersion) return;

                if (!place?.geometry?.location) {
                    showError(
                        'กรุณาเลือกสถานที่จากรายการที่ Google แนะนำ'
                    );
                    return;
                }

                setMarker(place.geometry.location, 17);
                clearAddressForNewPosition();

                placeSearchInput.value =
                    place.name && place.formatted_address
                        ? `${place.name}, ${place.formatted_address}`
                        : (place.formatted_address || place.name || '');

                showStatus('กำลังเติมข้อมูลที่อยู่...');

                await applyAddressToFields(
                    place.address_components || [],
                    place.formatted_address || '',
                    token
                );

                if (token !== requestVersion) return;

                showStatus(
                    'เลือกสถานที่และเติมข้อมูลที่อยู่เท่าที่พบแล้ว ' +
                    'กรุณาตรวจสอบก่อนบันทึก'
                );
            }

            /* ---------- ค้นหาจากที่อยู่ที่กรอก ---------- */

            async function searchFromAddress() {
                if (!geocoder) {
                    showError('Google Maps ยังโหลดไม่เสร็จ');
                    return;
                }

                if (!provinceSelect.value || !districtSelect.value) {
                    showError('กรุณาเลือกจังหวัดและอำเภอก่อน');
                    return;
                }

                const isBangkok =
                    normalizeThaiAddress(provinceSelect.value) ===
                    'กรุงเทพมหานคร';

                const parts = [
                    addressDetailInput.value.trim(),
                    subdistrictSelect.value
                        ? `${isBangkok ? 'แขวง' : 'ตำบล'}${subdistrictSelect.value}`
                        : '',
                    districtSelect.value
                        ? `${isBangkok ? 'เขต' : 'อำเภอ'}${districtSelect.value}`
                        : '',
                    provinceSelect.value,
                    zipcodeInput.value.trim(),
                    'ประเทศไทย'
                ].filter(Boolean);

                const token = ++requestVersion;

                searchAddressButton.disabled = true;
                searchAddressButton.textContent = 'กำลังค้นหา...';
                showStatus('กำลังค้นหาตำแหน่งจากที่อยู่...');

                try {
                    const response = await geocoder.geocode({
                        address: parts.join(' '),
                        componentRestrictions: {
                            country: 'TH'
                        },
                        language: 'th',
                        region: 'TH'
                    });

                    if (token !== requestVersion) return;

                    const result = response.results?.[0];

                    if (!result?.geometry?.location) {
                        throw new Error('ZERO_RESULTS');
                    }

                    setMarker(result.geometry.location, 16);

                    placeSearchInput.value =
                        result.formatted_address || '';

                    // เก็บบ้านเลขที่/หมู่ที่ผู้ใช้กรอกไว้
                    await applyAddressToFields(
                        result.address_components || [],
                        result.formatted_address || '',
                        token,
                        true
                    );

                    if (token !== requestVersion) return;

                    showStatus(
                        'ค้นหาและปักหมุดจากที่อยู่แล้ว ' +
                        'กรุณาตรวจสอบตำแหน่งหมุดก่อนบันทึก'
                    );
                } catch (error) {
                    if (token !== requestVersion) return;

                    showError(
                        'ไม่พบตำแหน่งจากที่อยู่นี้ ' +
                        'กรุณาค้นหาชื่อสถานที่หรือปักหมุดบนแผนที่'
                    );

                    console.error('Address search error:', error);
                } finally {
                    searchAddressButton.disabled = false;
                    searchAddressButton.textContent = 'ค้นหาจากที่อยู่';
                }
            }

            function clearMarker() {
                requestVersion++;

                if (marker) {
                    marker.setMap(null);
                    marker = null;
                }

                latitudeInput.value = '';
                longitudeInput.value = '';
                placeSearchInput.value = '';

                clearMessages();
                showAddressNotice('');
            }

            /* ---------- ตำแหน่งปัจจุบัน ---------- */

            function useCurrentLocation() {
                if (!map || !geocoder) {
                    showError('Google Maps ยังโหลดไม่เสร็จ');
                    return;
                }

                if (!navigator.geolocation) {
                    showError(
                        'เบราว์เซอร์นี้ไม่รองรับตำแหน่งปัจจุบัน'
                    );
                    return;
                }

                const token = ++requestVersion;

                currentLocationButton.disabled = true;
                currentLocationButton.textContent = 'กำลังค้นหา...';
                showStatus('กำลังค้นหาตำแหน่งปัจจุบัน...');

                navigator.geolocation.getCurrentPosition(
                    position => {
                        currentLocationButton.disabled = false;
                        currentLocationButton.textContent =
                            'ใช้ตำแหน่งปัจจุบัน';

                        if (token !== requestVersion) return;

                        const currentPosition = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        setMarker(currentPosition, 17);
                        reverseGeocode(currentPosition, token);
                    },
                    error => {
                        currentLocationButton.disabled = false;
                        currentLocationButton.textContent =
                            'ใช้ตำแหน่งปัจจุบัน';

                        if (token !== requestVersion) return;

                        showError(
                            'ไม่สามารถอ่านตำแหน่งปัจจุบันได้ ' +
                            'กรุณาอนุญาตสิทธิ์ตำแหน่งและลองอีกครั้ง'
                        );

                        console.error('Geolocation error:', error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            }

            searchAddressButton.addEventListener(
                'click',
                searchFromAddress
            );

            clearPinButton.addEventListener('click', clearMarker);

            currentLocationButton.addEventListener(
                'click',
                useCurrentLocation
            );

            /* ---------- เริ่มต้น Google Maps ---------- */

            window.initCampGoogleMap = function () {
                const existingLatitude =
                    Number.parseFloat(latitudeInput.value);

                const existingLongitude =
                    Number.parseFloat(longitudeInput.value);

                const hasExistingPosition =
                    Number.isFinite(existingLatitude) &&
                    Number.isFinite(existingLongitude);

                const initialPosition = hasExistingPosition
                    ? {
                        lat: existingLatitude,
                        lng: existingLongitude
                    }
                    : defaultPosition;

                map = new google.maps.Map(
                    document.getElementById('map'),
                    {
                        center: initialPosition,
                        zoom: hasExistingPosition ? 16 : 11,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: true,
                        clickableIcons: true
                    }
                );

                geocoder = new google.maps.Geocoder();

                placesService =
                    new google.maps.places.PlacesService(map);

                autocomplete =
                    new google.maps.places.Autocomplete(
                        placeSearchInput,
                        {
                            componentRestrictions: {
                                country: 'th'
                            },
                            fields: [
                                'place_id',
                                'name',
                                'formatted_address',
                                'geometry',
                                'address_components'
                            ]
                        }
                    );

                autocomplete.setOptions({
                    strictBounds: false
                });

                autocomplete.addListener('place_changed', () => {
                    const token = ++requestVersion;

                    selectGooglePlace(
                        autocomplete.getPlace(),
                        token
                    );
                });

                map.addListener('click', event => {
                    if (!event.latLng) return;

                    const token = ++requestVersion;

                    // ปักหมุดทันที แล้วจึงโหลดข้อมูลสถานที่
                    setMarker(event.latLng, 17);
                    clearAddressForNewPosition();
                    placeSearchInput.value = '';
                    showStatus('กำลังอ่านข้อมูลสถานที่...');

                    if (event.placeId) {
                        event.stop();

                        placesService.getDetails(
                            {
                                placeId: event.placeId,
                                fields: [
                                    'place_id',
                                    'name',
                                    'formatted_address',
                                    'geometry',
                                    'address_components'
                                ]
                            },
                            (place, status) => {
                                if (token !== requestVersion) return;

                                if (
                                    status ===
                                    google.maps.places.PlacesServiceStatus.OK &&
                                    place?.geometry?.location
                                ) {
                                    selectGooglePlace(place, token);
                                } else {
                                    reverseGeocode(event.latLng, token);
                                }
                            }
                        );

                        return;
                    }

                    reverseGeocode(event.latLng, token);
                });

                if (hasExistingPosition) {
                    // หน้าแก้ไขใช้ค่าที่บันทึกไว้ ไม่เขียนทับที่อยู่เดิม
                    setMarker(initialPosition);
                }
            };

            window.gm_authFailure = function () {
                showError(
                    'Google Maps โหลดไม่ได้ ' +
                    'กรุณาตรวจสอบ API Key, Billing และ Website restrictions'
                );
            };

            // โหลดรายการที่อยู่แยกจากแผนที่
            // แม้แผนที่โหลดไม่ได้ ผู้ใช้ยังเลือกจังหวัดได้
            loadThaiAddressData().catch(error => {
                showAddressNotice(
                    'โหลดรายการจังหวัด อำเภอ และตำบลไม่สำเร็จ ' +
                    'กรุณาตรวจสอบการเชื่อมต่อแล้วรีเฟรชหน้าเว็บ'
                );

                console.error('Initial address data error:', error);
            });
        })();
    </script>

    @if (config('services.google_maps.key'))
        <script
            async
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initCampGoogleMap&loading=async&language=th&region=TH"
        ></script>
    @else
        <script>
            document.getElementById('map_error').textContent =
                'ยังไม่ได้ตั้งค่า GOOGLE_MAPS_API_KEY ในไฟล์ .env';

            document.getElementById('map_error')
                .classList.remove('d-none');
        </script>
    @endif
@endsection