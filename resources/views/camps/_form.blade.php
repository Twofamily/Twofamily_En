<h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">
    ข้อมูลแคมป์
</h6>

<style>
    #map {
        width: 100%;
        height: 450px;
        border-radius: 10px;
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
</style>

<div class="mb-3">
    <label for="id_customer" class="form-label">
        ลูกค้า
    </label>

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
                @selected(
                    old(
                        'id_customer',
                        $camp->id_customer ?? ''
                    ) == $customer->id_customer
                )
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
        required
    >

    @error('name_camp')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="mb-3">
    <label for="status_camp" class="form-label">
        สถานะ
    </label>

    @php
        $selectedStatus = old(
            'status_camp',
            $camp->status_camp ?? 'active'
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

<div class="mb-3">
    <label for="place_search" class="form-label">
        ค้นหาตำแหน่งแคมป์
    </label>

    <input
        type="text"
        id="place_search"
        class="form-control"
        placeholder="ค้นหาชื่อแคมป์ บริษัท หมู่บ้าน หรือสถานที่"
        autocomplete="off"
    >

    <div class="form-text">
        พิมพ์ชื่อสถานที่ แล้วคลิกเลือกจากรายการที่ Google แนะนำ
    </div>
</div>

<div class="mb-3">
    <label for="address_detail" class="form-label">
        บ้านเลขที่ / หมู่ / รายละเอียดที่อยู่
    </label>

    <input
        type="text"
        id="address_detail"
        name="address_detail"
        value="{{ old(
            'address_detail',
            $camp->address_detail ?? ''
        ) }}"
        class="form-control @error('address_detail') is-invalid @enderror"
        placeholder="เช่น 123/45 หมู่ 6"
    >

    @error('address_detail')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="row g-3 mt-1">
    <div class="col-md-3">
        <label for="province" class="form-label">
            จังหวัด
        </label>

        <select
            id="province"
            name="province"
            class="form-select @error('province') is-invalid @enderror"
        >
            <option value="">— เลือกจังหวัด —</option>
        </select>

        @error('province')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="district" class="form-label">
            อำเภอ
        </label>

        <select
            id="district"
            name="district"
            class="form-select @error('district') is-invalid @enderror"
            disabled
        >
            <option value="">— เลือกอำเภอ —</option>
        </select>

        @error('district')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="subdistrict" class="form-label">
            ตำบล
        </label>

        <select
            id="subdistrict"
            name="subdistrict"
            class="form-select @error('subdistrict') is-invalid @enderror"
            disabled
        >
            <option value="">— เลือกตำบล —</option>
        </select>

        @error('subdistrict')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="zipcode" class="form-label">
            รหัสไปรษณีย์
        </label>

        <input
            type="text"
            id="zipcode"
            name="zipcode"
            value="{{ old('zipcode', $camp->zipcode ?? '') }}"
            class="form-control bg-light @error('zipcode') is-invalid @enderror"
            readonly
        >

        @error('zipcode')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>
</div>

<div class="row g-3 mt-2">
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

    <div class="col-12">
        <label class="form-label">
            ตำแหน่งแคมป์บนแผนที่
        </label>

        <div class="d-flex gap-2 flex-wrap mb-2">
            <button
                type="button"
                class="btn btn-outline-dark btn-sm"
                id="searchFromAddress"
            >
                ค้นหาจากที่อยู่ที่กรอก
            </button>

            <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                id="clearPin"
            >
                ล้างหมุด
            </button>

            <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                id="useCurrentLocation"
            >
                ใช้ตำแหน่งปัจจุบัน
            </button>
        </div>

        <div id="map"></div>

        <div class="form-text text-muted">
            ค้นหาชื่อสถานที่ คลิกบนแผนที่
            หรือจับหมุดลากเพื่อปรับตำแหน่ง
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

<div class="row g-3 mt-2 mb-4">
    <div class="col-md-4">
        <label for="contact_name" class="form-label">
            ชื่อผู้ติดต่อหน้างาน
        </label>

        <input
            type="text"
            id="contact_name"
            name="contact_name"
            value="{{ old(
                'contact_name',
                $camp->contact_name ?? ''
            ) }}"
            class="form-control @error('contact_name') is-invalid @enderror"
        >

        @error('contact_name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="contact_phone" class="form-label">
            เบอร์โทร (10 หลัก)
        </label>

        <input
            type="text"
            id="contact_phone"
            name="contact_phone"
            value="{{ old(
                'contact_phone',
                $camp->contact_phone ?? ''
            ) }}"
            class="form-control @error('contact_phone') is-invalid @enderror"
            maxlength="10"
            pattern="[0-9]{10}"
            inputmode="numeric"
            placeholder="เช่น 0812345678"
        >

        @error('contact_phone')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="note" class="form-label">
            หมายเหตุ
        </label>

        <input
            type="text"
            id="note"
            name="note"
            value="{{ old('note', $camp->note ?? '') }}"
            class="form-control @error('note') is-invalid @enderror"
        >

        @error('note')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>
</div>

<script>
    (() => {
        const thaiAddressApi =
            'https://raw.githubusercontent.com/kongvut/thai-province-data/master/api/latest/province_with_district_and_sub_district.json';

        const defaultPosition = {
            lat: 16.4419,
            lng: 102.8360
        };

        const oldProvince = @json(
            old('province', $camp->province ?? '')
        );

        const oldDistrict = @json(
            old('district', $camp->district ?? '')
        );

        const oldSubdistrict = @json(
            old('subdistrict', $camp->subdistrict ?? '')
        );

        const provinceSelect =
            document.getElementById('province');

        const districtSelect =
            document.getElementById('district');

        const subdistrictSelect =
            document.getElementById('subdistrict');

        const zipcodeInput =
            document.getElementById('zipcode');

        const addressDetailInput =
            document.getElementById('address_detail');

        const placeSearchInput =
            document.getElementById('place_search');

        const latitudeInput =
            document.getElementById('latitude');

        const longitudeInput =
            document.getElementById('longitude');

        const searchAddressButton =
            document.getElementById('searchFromAddress');

        const clearPinButton =
            document.getElementById('clearPin');

        const currentLocationButton =
            document.getElementById('useCurrentLocation');

        const statusElement =
            document.getElementById('map_status');

        const errorElement =
            document.getElementById('map_error');

        let thaiData = [];

        let addressDataLoaded = false;

        let map = null;

        let marker = null;

        let geocoder = null;

        let placesService = null;

        let autocomplete = null;

        function showStatus(message) {
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

        function findMatchingOption(select, value) {
            const normalizedValue =
                normalizeThaiAddress(value);

            return Array.from(select.options).find(
                function (option) {
                    return normalizeThaiAddress(
                        option.value
                    ) === normalizedValue;
                }
            );
        }

        function setSelectValue(select, value) {
            const matchingOption =
                findMatchingOption(select, value);

            if (!matchingOption) {
                return false;
            }

            select.value = matchingOption.value;

            select.dispatchEvent(
                new Event('change')
            );

            return true;
        }

        function getDistricts(province) {
            return (
                province?.amphure ||
                province?.districts ||
                province?.district ||
                []
            );
        }

        function getSubdistricts(district) {
            return (
                district?.tambon ||
                district?.sub_districts ||
                district?.subdistricts ||
                []
            );
        }

        function populateDistricts(provinceName) {
            districtSelect.innerHTML =
                '<option value="">— เลือกอำเภอ —</option>';

            subdistrictSelect.innerHTML =
                '<option value="">— เลือกตำบล —</option>';

            districtSelect.disabled = true;

            subdistrictSelect.disabled = true;

            zipcodeInput.value = '';

            const selectedProvince = thaiData.find(
                function (province) {
                    return normalizeThaiAddress(
                        province.name_th
                    ) === normalizeThaiAddress(
                        provinceName
                    );
                }
            );

            if (!selectedProvince) {
                return;
            }

            getDistricts(selectedProvince).forEach(
                function (district) {
                    districtSelect.add(
                        new Option(
                            district.name_th,
                            district.name_th
                        )
                    );
                }
            );

            districtSelect.disabled = false;
        }

        function populateSubdistricts(
            provinceName,
            districtName
        ) {
            subdistrictSelect.innerHTML =
                '<option value="">— เลือกตำบล —</option>';

            subdistrictSelect.disabled = true;

            zipcodeInput.value = '';

            const selectedProvince = thaiData.find(
                function (province) {
                    return normalizeThaiAddress(
                        province.name_th
                    ) === normalizeThaiAddress(
                        provinceName
                    );
                }
            );

            if (!selectedProvince) {
                return;
            }

            const selectedDistrict =
                getDistricts(selectedProvince).find(
                    function (district) {
                        return normalizeThaiAddress(
                            district.name_th
                        ) === normalizeThaiAddress(
                            districtName
                        );
                    }
                );

            if (!selectedDistrict) {
                return;
            }

            getSubdistricts(selectedDistrict).forEach(
                function (subdistrict) {
                    const option = new Option(
                        subdistrict.name_th,
                        subdistrict.name_th
                    );

                    option.dataset.zip =
                        subdistrict.zip_code || '';

                    subdistrictSelect.add(option);
                }
            );

            subdistrictSelect.disabled = false;
        }

        provinceSelect.addEventListener(
            'change',
            function () {
                populateDistricts(this.value);
            }
        );

        districtSelect.addEventListener(
            'change',
            function () {
                populateSubdistricts(
                    provinceSelect.value,
                    this.value
                );
            }
        );

        subdistrictSelect.addEventListener(
            'change',
            function () {
                const selectedOption =
                    this.selectedOptions[0];

                zipcodeInput.value =
                    selectedOption?.dataset?.zip || '';
            }
        );

        async function loadThaiAddressData() {
            try {
                const response = await fetch(
                    thaiAddressApi
                );

                if (!response.ok) {
                    throw new Error(
                        `HTTP ${response.status}`
                    );
                }

                thaiData = await response.json();

                thaiData.forEach(
                    function (province) {
                        provinceSelect.add(
                            new Option(
                                province.name_th,
                                province.name_th
                            )
                        );
                    }
                );

                addressDataLoaded = true;

                if (oldProvince) {
                    setSelectValue(
                        provinceSelect,
                        oldProvince
                    );

                    if (oldDistrict) {
                        setSelectValue(
                            districtSelect,
                            oldDistrict
                        );

                        if (oldSubdistrict) {
                            setSelectValue(
                                subdistrictSelect,
                                oldSubdistrict
                            );
                        }
                    }
                }
            } catch (error) {
                provinceSelect.innerHTML =
                    '<option value="">โหลดข้อมูลจังหวัดไม่สำเร็จ</option>';

                showError(
                    'โหลดข้อมูลจังหวัด อำเภอ และตำบลไม่สำเร็จ'
                );

                console.error(
                    'Thai address error:',
                    error
                );
            }
        }

        function findAddressComponent(
            components,
            types
        ) {
            return components.find(
                function (component) {
                    return types.some(
                        function (type) {
                            return component.types.includes(
                                type
                            );
                        }
                    );
                }
            );
        }

        function extractThaiAddress(components) {
            const provinceComponent =
                findAddressComponent(
                    components,
                    ['administrative_area_level_1']
                );

            const districtComponent =
                findAddressComponent(
                    components,
                    [
                        'administrative_area_level_2',
                        'locality'
                    ]
                );

            const subdistrictComponent =
                findAddressComponent(
                    components,
                    [
                        'sublocality_level_1',
                        'administrative_area_level_3',
                        'sublocality'
                    ]
                );

            const postalCodeComponent =
                findAddressComponent(
                    components,
                    ['postal_code']
                );

            const streetNumberComponent =
                findAddressComponent(
                    components,
                    ['street_number']
                );

            const routeComponent =
                findAddressComponent(
                    components,
                    ['route']
                );

            return {
                province:
                    provinceComponent?.long_name || '',

                district:
                    districtComponent?.long_name || '',

                subdistrict:
                    subdistrictComponent?.long_name || '',

                zipcode:
                    postalCodeComponent?.long_name || '',

                addressDetail: [
                    streetNumberComponent?.long_name,
                    routeComponent?.long_name
                ].filter(Boolean).join(' ')
            };
        }

        async function applyAddressToFields(
            components,
            overwriteAddressDetail = false
        ) {
            const address =
                extractThaiAddress(components);

            if (!addressDataLoaded) {
                await loadThaiAddressData();
            }

            if (address.province) {
                setSelectValue(
                    provinceSelect,
                    address.province
                );
            }

            if (address.district) {
                setSelectValue(
                    districtSelect,
                    address.district
                );
            }

            if (address.subdistrict) {
                setSelectValue(
                    subdistrictSelect,
                    address.subdistrict
                );
            }

            if (address.zipcode) {
                zipcodeInput.value =
                    address.zipcode;
            }

            if (
                overwriteAddressDetail &&
                address.addressDetail
            ) {
                addressDetailInput.value =
                    address.addressDetail;
            }
        }

        function setMarker(position, zoom = null) {
            if (!map) {
                return;
            }

            if (!marker) {
                marker = new google.maps.Marker({
                    map: map,

                    position: position,

                    draggable: true,

                    title: 'ตำแหน่งแคมป์'
                });

                marker.addListener(
                    'dragend',
                    function () {
                        const newPosition =
                            marker.getPosition();

                        updateCoordinates(
                            newPosition
                        );

                        reverseGeocode(
                            newPosition
                        );
                    }
                );
            } else {
                marker.setPosition(position);
            }

            updateCoordinates(position);

            map.panTo(position);

            if (zoom) {
                map.setZoom(zoom);
            }
        }

        function updateCoordinates(position) {
            latitudeInput.value =
                position.lat().toFixed(7);

            longitudeInput.value =
                position.lng().toFixed(7);
        }

        async function reverseGeocode(position) {
            if (!geocoder) {
                return;
            }

            clearError();

            showStatus('กำลังอ่านข้อมูลที่อยู่...');

            try {
                const response =
                    await geocoder.geocode({
                        location: position,

                        language: 'th',

                        region: 'TH'
                    });

                const result =
                    response.results?.[0];

                if (!result) {
                    showStatus(
                        'ปักหมุดแล้ว แต่ไม่พบข้อมูลที่อยู่'
                    );

                    return;
                }

                placeSearchInput.value =
                    result.formatted_address || '';

                await applyAddressToFields(
                    result.address_components || [],
                    false
                );

                showStatus(
                    'ปักหมุดและกรอกข้อมูลที่อยู่อัตโนมัติแล้ว'
                );
            } catch (error) {
                showStatus(
                    'ปักหมุดแล้ว แต่ไม่สามารถอ่านข้อมูลที่อยู่ได้'
                );

                console.error(
                    'Reverse geocoding error:',
                    error
                );
            }
        }

        async function selectGooglePlace(place) {
            if (!place?.geometry?.location) {
                showError(
                    'กรุณาเลือกสถานที่จากรายการที่ Google แนะนำ'
                );

                return;
            }

            clearError();

            const location =
                place.geometry.location;

            placeSearchInput.value =
                place.name && place.formatted_address
                    ? `${place.name}, ${place.formatted_address}`
                    : (
                        place.formatted_address ||
                        place.name ||
                        ''
                    );

            setMarker(location, 17);

            await applyAddressToFields(
                place.address_components || [],
                false
            );

            showStatus(
                'เลือกตำแหน่งแคมป์เรียบร้อยแล้ว'
            );
        }

        async function searchFromAddress() {
            if (!geocoder) {
                showError(
                    'Google Maps ยังโหลดไม่เสร็จ'
                );

                return;
            }

            const addressParts = [
                addressDetailInput.value,
                subdistrictSelect.value
                    ? `ตำบล${subdistrictSelect.value}`
                    : '',
                districtSelect.value
                    ? `อำเภอ${districtSelect.value}`
                    : '',
                provinceSelect.value
                    ? `จังหวัด${provinceSelect.value}`
                    : '',
                zipcodeInput.value,
                'ประเทศไทย'
            ].filter(Boolean);

            if (
                !provinceSelect.value ||
                !districtSelect.value
            ) {
                showError(
                    'กรุณาเลือกจังหวัดและอำเภอก่อน'
                );

                return;
            }

            searchAddressButton.disabled = true;

            searchAddressButton.textContent =
                'กำลังค้นหา...';

            clearError();

            try {
                const response =
                    await geocoder.geocode({
                        address:
                            addressParts.join(' '),

                        componentRestrictions: {
                            country: 'TH'
                        },

                        language: 'th',

                        region: 'TH'
                    });

                const result =
                    response.results?.[0];

                if (!result?.geometry?.location) {
                    throw new Error(
                        'ZERO_RESULTS'
                    );
                }

                setMarker(
                    result.geometry.location,
                    16
                );

                placeSearchInput.value =
                    result.formatted_address || '';

                await applyAddressToFields(
                    result.address_components || [],
                    false
                );

                showStatus(
                    'ค้นหาและปักหมุดจากที่อยู่แล้ว'
                );
            } catch (error) {
                showError(
                    'ไม่พบตำแหน่งจากที่อยู่นี้ กรุณาค้นหาชื่อสถานที่หรือปักหมุดบนแผนที่'
                );

                console.error(
                    'Address search error:',
                    error
                );
            } finally {
                searchAddressButton.disabled = false;

                searchAddressButton.textContent =
                    'ค้นหาจากที่อยู่ที่กรอก';
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
                showError(
                    'เบราว์เซอร์นี้ไม่รองรับการค้นหาตำแหน่งปัจจุบัน'
                );

                return;
            }

            currentLocationButton.disabled = true;

            currentLocationButton.textContent =
                'กำลังค้นหาตำแหน่ง...';

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const currentPosition =
                        new google.maps.LatLng(
                            position.coords.latitude,
                            position.coords.longitude
                        );

                    setMarker(
                        currentPosition,
                        17
                    );

                    reverseGeocode(
                        currentPosition
                    );

                    currentLocationButton.disabled = false;

                    currentLocationButton.textContent =
                        'ใช้ตำแหน่งปัจจุบัน';
                },

                function () {
                    showError(
                        'ไม่สามารถอ่านตำแหน่งปัจจุบันได้ กรุณาอนุญาตสิทธิ์ตำแหน่ง'
                    );

                    currentLocationButton.disabled = false;

                    currentLocationButton.textContent =
                        'ใช้ตำแหน่งปัจจุบัน';
                },

                {
                    enableHighAccuracy: true,

                    timeout: 10000
                }
            );
        }

        window.initCampGoogleMap = async function () {
            await loadThaiAddressData();

            const existingLatitude =
                Number.parseFloat(
                    latitudeInput.value
                );

            const existingLongitude =
                Number.parseFloat(
                    longitudeInput.value
                );

            const hasExistingPosition =
                Number.isFinite(existingLatitude) &&
                Number.isFinite(existingLongitude);

            const initialPosition =
                hasExistingPosition
                    ? {
                        lat: existingLatitude,
                        lng: existingLongitude
                    }
                    : defaultPosition;

            map = new google.maps.Map(
                document.getElementById('map'),
                {
                    center: initialPosition,

                    zoom:
                        hasExistingPosition
                            ? 16
                            : 11,

                    mapTypeControl: false,

                    streetViewControl: false,

                    fullscreenControl: true,

                    clickableIcons: true
                }
            );

            geocoder =
                new google.maps.Geocoder();

            placesService =
                new google.maps.places.PlacesService(
                    map
                );

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

            autocomplete.addListener(
                'place_changed',
                function () {
                    selectGooglePlace(
                        autocomplete.getPlace()
                    );
                }
            );

            map.addListener(
                'click',
                function (event) {
                    if (!event.latLng) {
                        return;
                    }

                    if (
                        event.placeId &&
                        placesService
                    ) {
                        event.stop();

                        placesService.getDetails(
                            {
                                placeId:
                                    event.placeId,

                                fields: [
                                    'place_id',
                                    'name',
                                    'formatted_address',
                                    'geometry',
                                    'address_components'
                                ]
                            },

                            function (place, status) {
                                if (
                                    status ===
                                    google.maps.places
                                        .PlacesServiceStatus.OK
                                ) {
                                    selectGooglePlace(
                                        place
                                    );

                                    return;
                                }

                                setMarker(
                                    event.latLng,
                                    17
                                );

                                reverseGeocode(
                                    event.latLng
                                );
                            }
                        );

                        return;
                    }

                    setMarker(
                        event.latLng,
                        17
                    );

                    reverseGeocode(
                        event.latLng
                    );
                }
            );

            if (hasExistingPosition) {
                setMarker(
                    new google.maps.LatLng(
                        existingLatitude,
                        existingLongitude
                    )
                );
            }

            searchAddressButton.addEventListener(
                'click',
                searchFromAddress
            );

            clearPinButton.addEventListener(
                'click',
                clearMarker
            );

            currentLocationButton.addEventListener(
                'click',
                useCurrentLocation
            );

            setTimeout(
                function () {
                    document
                        .querySelectorAll(
                            '.pac-container'
                        )
                        .forEach(
                            function (container) {
                                container.addEventListener(
                                    'mousedown',
                                    function (event) {
                                        event.stopPropagation();
                                    }
                                );
                            }
                        );
                },
                500
            );
        };

        window.gm_authFailure = function () {
            showError(
                'Google Maps โหลดไม่ได้ กรุณาตรวจสอบ API Key, Billing และ Website restrictions'
            );
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
        const mapError =
            document.getElementById('map_error');

        mapError.textContent =
            'ยังไม่ได้ตั้งค่า GOOGLE_MAPS_API_KEY ในไฟล์ .env';

        mapError.classList.remove('d-none');
    </script>
@endif