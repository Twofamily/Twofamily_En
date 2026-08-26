@extends('layout')

@php
    // ห้ามใช้เพียง isset($camp) เพราะหน้า create อาจส่ง new Camp() มาให้
    $isEdit = isset($camp) && $camp->exists;
@endphp

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
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">กรุณาตรวจสอบข้อมูล</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ $isEdit
                ? route('camps.update', ['camp' => $camp->getRouteKey()])
                : route('camps.store') }}"
        >
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">
                                ข้อมูลแคมป์
                            </h6>

                            <div class="mb-3">
                                <label for="id_customer" class="form-label">ลูกค้า</label>
                                <select
                                    id="id_customer"
                                    name="id_customer"
                                    class="form-select @error('id_customer') is-invalid @enderror"
                                    required
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
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="name_camp" class="form-label">ชื่อแคมป์</label>
                                <input
                                    type="text"
                                    id="name_camp"
                                    name="name_camp"
                                    value="{{ old('name_camp', $camp->name_camp ?? '') }}"
                                    class="form-control @error('name_camp') is-invalid @enderror"
                                    placeholder="เช่น แคมป์บ้านไผ่ เฟส 2"
                                    required
                                >
                                @error('name_camp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="status_camp" class="form-label">สถานะ</label>
                                @php
                                    $selectedStatus = old('status_camp', $camp->status_camp ?? 'active');
                                @endphp
                                <select
                                    id="status_camp"
                                    name="status_camp"
                                    class="form-select @error('status_camp') is-invalid @enderror"
                                    required
                                >
                                    @foreach (\App\Models\Camp::STATUS_LABELS as $value => $label)
                                        <option value="{{ $value }}" @selected($selectedStatus === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status_camp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <h6 class="text-muted fw-semibold border-bottom pb-2 mt-4 mb-3">
                                ข้อมูลที่อยู่
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
                                @error('address_detail')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="province" class="form-label">จังหวัด</label>
                                    <select
                                        id="province"
                                        name="province"
                                        class="form-select @error('province') is-invalid @enderror"
                                    >
                                        <option value="">— เลือกจังหวัด —</option>
                                    </select>
                                    @error('province')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="district" class="form-label">อำเภอ</label>
                                    <select
                                        id="district"
                                        name="district"
                                        class="form-select @error('district') is-invalid @enderror"
                                        disabled
                                    >
                                        <option value="">— เลือกอำเภอ —</option>
                                    </select>
                                    @error('district')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="subdistrict" class="form-label">ตำบล</label>
                                    <select
                                        id="subdistrict"
                                        name="subdistrict"
                                        class="form-select @error('subdistrict') is-invalid @enderror"
                                        disabled
                                    >
                                        <option value="">— เลือกตำบล —</option>
                                    </select>
                                    @error('subdistrict')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="zipcode" class="form-label">รหัสไปรษณีย์</label>
                                    <input
                                        type="text"
                                        id="zipcode"
                                        name="zipcode"
                                        value="{{ old('zipcode', $camp->zipcode ?? '') }}"
                                        class="form-control bg-light @error('zipcode') is-invalid @enderror"
                                        readonly
                                    >
                                    @error('zipcode')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="latitude" class="form-label">ละติจูด</label>
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
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="longitude" class="form-label">ลองจิจูด</label>
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
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <h6 class="text-muted fw-semibold border-bottom pb-2 mt-4 mb-3">
                                ข้อมูลติดต่อ
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="contact_name" class="form-label">ชื่อผู้ติดต่อหน้างาน</label>
                                    <input
                                        type="text"
                                        id="contact_name"
                                        name="contact_name"
                                        value="{{ old('contact_name', $camp->contact_name ?? '') }}"
                                        class="form-control @error('contact_name') is-invalid @enderror"
                                    >
                                    @error('contact_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="contact_phone" class="form-label">เบอร์โทร (10 หลัก)</label>
                                    <input
                                        type="text"
                                        id="contact_phone"
                                        name="contact_phone"
                                        value="{{ old('contact_phone', $camp->contact_phone ?? '') }}"
                                        class="form-control @error('contact_phone') is-invalid @enderror"
                                        maxlength="10"
                                        pattern="[0-9]{10}"
                                        inputmode="numeric"
                                        placeholder="เช่น 0812345678"
                                    >
                                    @error('contact_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="note" class="form-label">หมายเหตุ</label>
                                    <textarea
                                        id="note"
                                        name="note"
                                        rows="3"
                                        class="form-control @error('note') is-invalid @enderror"
                                    >{{ old('note', $camp->note ?? '') }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
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
                                เลือกตำแหน่งบนแผนที่
                            </h6>

                            <div class="mb-3">
                                <label for="place_search" class="form-label">ค้นหาสถานที่</label>
                                <input
                                    type="text"
                                    id="place_search"
                                    class="form-control"
                                    placeholder="ค้นหาชื่อแคมป์ บริษัท หมู่บ้าน หรือสถานที่"
                                    autocomplete="off"
                                >
                                <div class="form-text">
                                    พิมพ์ชื่อสถานที่ แล้วเลือกจากรายการที่ Google แนะนำ
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <button type="button" class="btn btn-outline-dark btn-sm" id="searchFromAddress">
                                    ค้นหาจากที่อยู่
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="useCurrentLocation">
                                    ใช้ตำแหน่งปัจจุบัน
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearPin">
                                    ล้างหมุด
                                </button>
                            </div>

                            <div id="map"></div>
                            <div class="form-text text-muted mt-2">
                                ค้นหาสถานที่ คลิกบนแผนที่ หรือลากหมุดเพื่อปรับตำแหน่ง
                            </div>

                            <div id="map_status" class="alert alert-info mt-3 d-none" role="status"></div>
                            <div id="map_error" class="alert alert-danger mt-3 d-none" role="alert"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('camps.index') }}" class="btn btn-outline-secondary">
                    ยกเลิก
                </a>
                <button type="submit" class="btn btn-dark">
                    {{ $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มแคมป์' }}
                </button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const thaiAddressApi =
                'https://raw.githubusercontent.com/kongvut/thai-province-data/master/api/latest/province_with_district_and_sub_district.json';

            const defaultPosition = { lat: 16.4419, lng: 102.8360 };
            const oldProvince = @json(old('province', $camp->province ?? ''));
            const oldDistrict = @json(old('district', $camp->district ?? ''));
            const oldSubdistrict = @json(old('subdistrict', $camp->subdistrict ?? ''));

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

            let thaiData = [];
            let addressDataPromise = null;
            let map = null;
            let marker = null;
            let geocoder = null;
            let placesService = null;
            let autocomplete = null;

            function showStatus(message) {
                errorElement.classList.add('d-none');
                statusElement.textContent = message;
                statusElement.classList.remove('d-none');
            }

            function clearStatus() {
                statusElement.textContent = '';
                statusElement.classList.add('d-none');
            }

            function showError(message) {
                clearStatus();
                errorElement.textContent = message;
                errorElement.classList.remove('d-none');
            }

            function clearError() {
                errorElement.textContent = '';
                errorElement.classList.add('d-none');
            }

            function normalizeThaiAddress(value) {
                return String(value || '')
                    .trim()
                    .replace(/^จังหวัด/, '')
                    .replace(/^จ\./, '')
                    .replace(/^อำเภอ/, '')
                    .replace(/^อ\./, '')
                    .replace(/^เขต/, '')
                    .replace(/^ตำบล/, '')
                    .replace(/^ต\./, '')
                    .replace(/^แขวง/, '')
                    .replace(/\s+/g, '');
            }

            function getDistricts(province) {
                return province?.amphure || province?.districts || province?.district || [];
            }

            function getSubdistricts(district) {
                return district?.tambon || district?.sub_districts || district?.subdistricts || [];
            }

            function findMatchingOption(select, value) {
                const target = normalizeThaiAddress(value);
                return Array.from(select.options).find(
                    option => normalizeThaiAddress(option.value) === target
                );
            }

            function setSelectValue(select, value) {
                const option = findMatchingOption(select, value);
                if (!option) return false;
                select.value = option.value;
                select.dispatchEvent(new Event('change'));
                return true;
            }

            function populateDistricts(provinceName) {
                districtSelect.innerHTML = '<option value="">— เลือกอำเภอ —</option>';
                subdistrictSelect.innerHTML = '<option value="">— เลือกตำบล —</option>';
                districtSelect.disabled = true;
                subdistrictSelect.disabled = true;
                zipcodeInput.value = '';

                const province = thaiData.find(
                    item => normalizeThaiAddress(item.name_th) === normalizeThaiAddress(provinceName)
                );
                if (!province) return;

                getDistricts(province).forEach(district => {
                    districtSelect.add(new Option(district.name_th, district.name_th));
                });
                districtSelect.disabled = false;
            }

            function populateSubdistricts(provinceName, districtName) {
                subdistrictSelect.innerHTML = '<option value="">— เลือกตำบล —</option>';
                subdistrictSelect.disabled = true;
                zipcodeInput.value = '';

                const province = thaiData.find(
                    item => normalizeThaiAddress(item.name_th) === normalizeThaiAddress(provinceName)
                );
                if (!province) return;

                const district = getDistricts(province).find(
                    item => normalizeThaiAddress(item.name_th) === normalizeThaiAddress(districtName)
                );
                if (!district) return;

                getSubdistricts(district).forEach(subdistrict => {
                    const option = new Option(subdistrict.name_th, subdistrict.name_th);
                    option.dataset.zip = subdistrict.zip_code || '';
                    subdistrictSelect.add(option);
                });
                subdistrictSelect.disabled = false;
            }

            provinceSelect.addEventListener('change', function () {
                populateDistricts(this.value);
            });

            districtSelect.addEventListener('change', function () {
                populateSubdistricts(provinceSelect.value, this.value);
            });

            subdistrictSelect.addEventListener('change', function () {
                zipcodeInput.value = this.selectedOptions[0]?.dataset?.zip || '';
            });

            function loadThaiAddressData() {
                if (addressDataPromise) return addressDataPromise;

                addressDataPromise = fetch(thaiAddressApi)
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        thaiData = data;
                        provinceSelect.innerHTML = '<option value="">— เลือกจังหวัด —</option>';

                        thaiData.forEach(province => {
                            provinceSelect.add(new Option(province.name_th, province.name_th));
                        });

                        if (oldProvince) {
                            setSelectValue(provinceSelect, oldProvince);
                            if (oldDistrict) {
                                setSelectValue(districtSelect, oldDistrict);
                                if (oldSubdistrict) {
                                    setSelectValue(subdistrictSelect, oldSubdistrict);
                                }
                            }
                        }
                    })
                    .catch(error => {
                        addressDataPromise = null;
                        provinceSelect.innerHTML = '<option value="">โหลดข้อมูลจังหวัดไม่สำเร็จ</option>';
                        showError('โหลดข้อมูลจังหวัด อำเภอ และตำบลไม่สำเร็จ');
                        console.error('Thai address error:', error);
                        throw error;
                    });

                return addressDataPromise;
            }

            function findAddressComponent(components, types) {
                return components.find(component =>
                    types.some(type => component.types.includes(type))
                );
            }

            function extractThaiAddress(components) {
                const province = findAddressComponent(components, ['administrative_area_level_1']);
                const district = findAddressComponent(components, ['administrative_area_level_2', 'locality']);
                const subdistrict = findAddressComponent(components, [
                    'sublocality_level_1',
                    'administrative_area_level_3',
                    'sublocality'
                ]);
                const postalCode = findAddressComponent(components, ['postal_code']);
                const streetNumber = findAddressComponent(components, ['street_number']);
                const route = findAddressComponent(components, ['route']);

                return {
                    province: province?.long_name || '',
                    district: district?.long_name || '',
                    subdistrict: subdistrict?.long_name || '',
                    zipcode: postalCode?.long_name || '',
                    addressDetail: [streetNumber?.long_name, route?.long_name].filter(Boolean).join(' ')
                };
            }

            async function applyAddressToFields(components, overwriteAddressDetail = false) {
                const address = extractThaiAddress(components);

                try {
                    await loadThaiAddressData();
                } catch (error) {
                    return;
                }

                if (address.province) setSelectValue(provinceSelect, address.province);
                if (address.district) setSelectValue(districtSelect, address.district);
                if (address.subdistrict) setSelectValue(subdistrictSelect, address.subdistrict);
                if (address.zipcode) zipcodeInput.value = address.zipcode;

                if (overwriteAddressDetail && address.addressDetail) {
                    addressDetailInput.value = address.addressDetail;
                }
            }

            function positionLat(position) {
                return typeof position.lat === 'function' ? position.lat() : position.lat;
            }

            function positionLng(position) {
                return typeof position.lng === 'function' ? position.lng() : position.lng;
            }

            function updateCoordinates(position) {
                latitudeInput.value = Number(positionLat(position)).toFixed(7);
                longitudeInput.value = Number(positionLng(position)).toFixed(7);
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

                    marker.addListener('dragend', () => {
                        const newPosition = marker.getPosition();
                        updateCoordinates(newPosition);
                        reverseGeocode(newPosition);
                    });
                } else {
                    marker.setPosition(position);
                }

                updateCoordinates(position);
                map.panTo(position);
                if (zoom !== null) map.setZoom(zoom);
            }

            async function reverseGeocode(position) {
                if (!geocoder) return;
                clearError();
                showStatus('กำลังอ่านข้อมูลที่อยู่...');

                try {
                    const response = await geocoder.geocode({
                        location: position,
                        language: 'th',
                        region: 'TH'
                    });
                    const result = response.results?.[0];

                    if (!result) {
                        showStatus('ปักหมุดแล้ว แต่ไม่พบข้อมูลที่อยู่');
                        return;
                    }

                    placeSearchInput.value = result.formatted_address || '';
                    await applyAddressToFields(result.address_components || [], false);
                    showStatus('ปักหมุดและกรอกข้อมูลที่อยู่อัตโนมัติแล้ว');
                } catch (error) {
                    showStatus('ปักหมุดแล้ว แต่ไม่สามารถอ่านข้อมูลที่อยู่ได้');
                    console.error('Reverse geocoding error:', error);
                }
            }

            async function selectGooglePlace(place) {
                if (!place?.geometry?.location) {
                    showError('กรุณาเลือกสถานที่จากรายการที่ Google แนะนำ');
                    return;
                }

                clearError();
                placeSearchInput.value = place.name && place.formatted_address
                    ? `${place.name}, ${place.formatted_address}`
                    : (place.formatted_address || place.name || '');

                setMarker(place.geometry.location, 17);
                await applyAddressToFields(place.address_components || [], false);
                showStatus('เลือกตำแหน่งแคมป์เรียบร้อยแล้ว');
            }

            async function searchFromAddress() {
                if (!geocoder) {
                    showError('Google Maps ยังโหลดไม่เสร็จ');
                    return;
                }

                if (!provinceSelect.value || !districtSelect.value) {
                    showError('กรุณาเลือกจังหวัดและอำเภอก่อน');
                    return;
                }

                const parts = [
                    addressDetailInput.value,
                    subdistrictSelect.value ? `ตำบล${subdistrictSelect.value}` : '',
                    districtSelect.value ? `อำเภอ${districtSelect.value}` : '',
                    provinceSelect.value ? `จังหวัด${provinceSelect.value}` : '',
                    zipcodeInput.value,
                    'ประเทศไทย'
                ].filter(Boolean);

                searchAddressButton.disabled = true;
                searchAddressButton.textContent = 'กำลังค้นหา...';
                clearError();

                try {
                    const response = await geocoder.geocode({
                        address: parts.join(' '),
                        componentRestrictions: { country: 'TH' },
                        language: 'th',
                        region: 'TH'
                    });
                    const result = response.results?.[0];
                    if (!result?.geometry?.location) throw new Error('ZERO_RESULTS');

                    setMarker(result.geometry.location, 16);
                    placeSearchInput.value = result.formatted_address || '';
                    await applyAddressToFields(result.address_components || [], false);
                    showStatus('ค้นหาและปักหมุดจากที่อยู่แล้ว');
                } catch (error) {
                    showError('ไม่พบตำแหน่งจากที่อยู่นี้ กรุณาค้นหาชื่อสถานที่หรือปักหมุดบนแผนที่');
                    console.error('Address search error:', error);
                } finally {
                    searchAddressButton.disabled = false;
                    searchAddressButton.textContent = 'ค้นหาจากที่อยู่';
                }
            }

            function clearMarker() {
                if (marker) {
                    marker.setMap(null);
                    marker = null;
                }
                latitudeInput.value = '';
                longitudeInput.value = '';
                placeSearchInput.value = '';
                clearStatus();
                clearError();
            }

            function useCurrentLocation() {
                if (!navigator.geolocation) {
                    showError('เบราว์เซอร์นี้ไม่รองรับตำแหน่งปัจจุบัน');
                    return;
                }

                currentLocationButton.disabled = true;
                currentLocationButton.textContent = 'กำลังค้นหา...';

                navigator.geolocation.getCurrentPosition(
                    position => {
                        const currentPosition = new google.maps.LatLng(
                            position.coords.latitude,
                            position.coords.longitude
                        );
                        setMarker(currentPosition, 17);
                        reverseGeocode(currentPosition);
                        currentLocationButton.disabled = false;
                        currentLocationButton.textContent = 'ใช้ตำแหน่งปัจจุบัน';
                    },
                    () => {
                        showError('ไม่สามารถอ่านตำแหน่งปัจจุบันได้ กรุณาอนุญาตสิทธิ์ตำแหน่ง');
                        currentLocationButton.disabled = false;
                        currentLocationButton.textContent = 'ใช้ตำแหน่งปัจจุบัน';
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            }

            window.initCampGoogleMap = async function () {
                try {
                    await loadThaiAddressData();
                } catch (error) {
                    // แผนที่ยังใช้งานได้ แม้ API ที่อยู่ไทยโหลดไม่สำเร็จ
                }

                const existingLatitude = Number.parseFloat(latitudeInput.value);
                const existingLongitude = Number.parseFloat(longitudeInput.value);
                const hasExistingPosition =
                    Number.isFinite(existingLatitude) && Number.isFinite(existingLongitude);
                const initialPosition = hasExistingPosition
                    ? { lat: existingLatitude, lng: existingLongitude }
                    : defaultPosition;

                map = new google.maps.Map(document.getElementById('map'), {
                    center: initialPosition,
                    zoom: hasExistingPosition ? 16 : 11,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                    clickableIcons: true
                });

                geocoder = new google.maps.Geocoder();
                placesService = new google.maps.places.PlacesService(map);
                autocomplete = new google.maps.places.Autocomplete(placeSearchInput, {
                    componentRestrictions: { country: 'th' },
                    fields: [
                        'place_id',
                        'name',
                        'formatted_address',
                        'geometry',
                        'address_components'
                    ]
                });

                autocomplete.setOptions({ strictBounds: false });
                autocomplete.addListener('place_changed', () => {
                    selectGooglePlace(autocomplete.getPlace());
                });

                map.addListener('click', event => {
                    if (!event.latLng) return;

                    if (event.placeId && placesService) {
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
                                if (status === google.maps.places.PlacesServiceStatus.OK) {
                                    selectGooglePlace(place);
                                } else {
                                    setMarker(event.latLng, 17);
                                    reverseGeocode(event.latLng);
                                }
                            }
                        );
                        return;
                    }

                    setMarker(event.latLng, 17);
                    reverseGeocode(event.latLng);
                });

                if (hasExistingPosition) {
                    setMarker(new google.maps.LatLng(existingLatitude, existingLongitude));
                }

                searchAddressButton.addEventListener('click', searchFromAddress);
                clearPinButton.addEventListener('click', clearMarker);
                currentLocationButton.addEventListener('click', useCurrentLocation);
            };

            window.gm_authFailure = function () {
                showError('Google Maps โหลดไม่ได้ กรุณาตรวจสอบ API Key, Billing และ Website restrictions');
            };
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
            document.getElementById('map_error').classList.remove('d-none');
        </script>
    @endif
@endsection
