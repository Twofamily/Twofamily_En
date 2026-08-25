@extends('layout')

@section('namepage')
    <div class="container">
        <h3>
            {{ isset($fuel_record) ? 'แก้ไขบันทึกน้ำมัน' : 'เพิ่มบันทึกน้ำมัน' }}
        </h3>
    </div>
@endsection

@section('content')
    <style>
        #map {
            width: 100%;
            height: 550px;
            border-radius: 12px;
        }

        .map-help {
            font-size: 14px;
            color: #6c757d;
        }

        /*
         * รายการค้นหาของ Google จะถูกสร้างไว้นอกฟอร์ม
         * จึงต้องกำหนด z-index และ pointer-events ให้คลิกได้
         */
        .pac-container {
            z-index: 99999 !important;
            pointer-events: auto !important;
            border-radius: 0 0 8px 8px;
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

        .pac-logo::after {
            pointer-events: none;
        }

        .location-input-active {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
    </style>

    <div class="container py-3">
        <form
            id="fuel_record_form"
            method="POST"
            action="{{ isset($fuel_record)
                ? route('fuel_records.update', $fuel_record->id_fuel_record)
                : route('fuel_records.store') }}"
        >
            @csrf

            @if (isset($fuel_record))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="date_record" class="form-label">
                            วันที่
                        </label>

                        <input
                            type="date"
                            id="date_record"
                            name="date_record"
                            class="form-control"
                            value="{{ old(
                                'date_record',
                                $fuel_record->date_record ?? now()->format('Y-m-d')
                            ) }}"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="truck_select" class="form-label">
                            รถบรรทุก
                        </label>

                        <select
                            name="trucks_id_truck"
                            id="truck_select"
                            class="form-select"
                            required
                        >
                            @foreach ($trucks as $truck)
                                <option
                                    value="{{ $truck->id_truck }}"
                                    data-fuel-rate="{{ $truck->fuel_rate }}"
                                    @selected(
                                        old(
                                            'trucks_id_truck',
                                            $fuel_record->trucks_id_truck ?? ''
                                        ) == $truck->id_truck
                                    )
                                >
                                    {{ $truck->brand_truck }}
                                    ({{ $truck->id_truck }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="start_point" class="form-label">
                            จุดเริ่มต้น
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            id="start_point"
                            name="start_point"
                            class="form-control"
                            placeholder="ค้นหาสถานที่ เช่น Campuslife ขอนแก่น"
                            value="{{ old(
                                'start_point',
                                $fuel_record->start_point ?? ''
                            ) }}"
                            autocomplete="off"
                            required
                        >

                        <small class="text-muted">
                            พิมพ์แล้วคลิกเลือกรายการ หรือกดลูกศรลงแล้วกด Enter
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="destination" class="form-label">
                            ปลายทาง
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            id="destination"
                            name="destination"
                            class="form-control"
                            placeholder=""
                            value="{{ old(
                                'destination',
                                $fuel_record->destination ?? ''
                            ) }}"
                            autocomplete="off"
                            required
                        >

                    </div>

                    <div class="mb-3 d-flex gap-2 flex-wrap">
                        <button
                            type="button"
                            id="calculate_route_button"
                            class="btn btn-primary"
                        >
                            คำนวณเส้นทาง
                        </button>

                        <button
                            type="button"
                            id="swap_locations_button"
                            class="btn btn-outline-primary"
                        >
                            สลับต้นทาง-ปลายทาง
                        </button>

                        <button
                            type="button"
                            id="clear_route_button"
                            class="btn btn-outline-secondary"
                        >
                            ล้างเส้นทาง
                        </button>
                    </div>

                    <div class="mb-3">
                        <label for="distance" class="form-label">
                            ระยะทาง (กม.)
                        </label>

                        <input
                            type="number"
                            id="distance"
                            name="distance"
                            class="form-control bg-light"
                            step="0.01"
                            min="0"
                            value="{{ old(
                                'distance',
                                $fuel_record->distance ?? ''
                            ) }}"
                            readonly
                        >
                    </div>

                    <div class="mb-3">
                        <label for="cost_fuel" class="form-label">
                            ราคาน้ำมัน (บาท/ลิตร)
                        </label>

                        <input
                            type="number"
                            id="cost_fuel"
                            name="cost_fuel"
                            class="form-control bg-light"
                            step="0.01"
                            min="0"
                            value="{{ old(
                                'cost_fuel',
                                $fuel_record->cost_fuel ?? ($dieselPrice ?? '')
                            ) }}"
                            readonly
                        >
                    </div>

                    <div class="mb-3">
                        <label for="show_fuel_rate" class="form-label">
                            อัตราสิ้นเปลือง (กม./ลิตร)
                        </label>

                        <input
                            type="number"
                            id="show_fuel_rate"
                            class="form-control bg-light"
                            step="0.01"
                            placeholder="เลือกตัวรถเพื่อแสดงค่า"
                            readonly
                        >
                    </div>

                    <div class="mb-3">
                        <label for="cost_fuel_total" class="form-label">
                            ค่าน้ำมันรวม (บาท)
                        </label>

                        <input
                            type="number"
                            id="cost_fuel_total"
                            name="cost_fuel_total"
                            class="form-control bg-light"
                            step="0.01"
                            min="0"
                            value="{{ old(
                                'cost_fuel_total',
                                $fuel_record->cost_fuel_total ?? ''
                            ) }}"
                            readonly
                        >
                    </div>

                    <button type="submit" class="btn btn-success">
                        บันทึก
                    </button>
                </div>

                <div class="col-md-6">
                    <div id="map"></div>

                    <div class="map-help mt-2">
                        คลิกช่องต้นทางหรือปลายทางก่อน
                        จากนั้นคลิกตำแหน่งหรือหมุดสถานที่บนแผนที่
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
        </form>
    </div>

    <script>
        (() => {
            const defaultCenter = {
                lat: 16.4322,
                lng: 102.8236
            };

            const form = document.getElementById('fuel_record_form');
            const startInput = document.getElementById('start_point');
            const destinationInput = document.getElementById('destination');
            const distanceInput = document.getElementById('distance');
            const fuelPriceInput = document.getElementById('cost_fuel');
            const fuelTotalInput = document.getElementById('cost_fuel_total');
            const fuelRateInput = document.getElementById('show_fuel_rate');
            const truckSelect = document.getElementById('truck_select');

            const calculateButton = document.getElementById(
                'calculate_route_button'
            );

            const swapButton = document.getElementById(
                'swap_locations_button'
            );

            const clearButton = document.getElementById(
                'clear_route_button'
            );

            const statusElement = document.getElementById('map_status');
            const errorElement = document.getElementById('map_error');

            let map = null;
            let directionsService = null;
            let directionsRenderer = null;
            let placesService = null;
            let temporaryMarker = null;

            let startLocation = null;
            let destinationLocation = null;
            let activeField = 'start';

            let startAutocomplete = null;
            let destinationAutocomplete = null;

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

            function clearRouteValues() {
                distanceInput.value = '';
                fuelTotalInput.value = '';
            }

            function removeTemporaryMarker() {
                if (temporaryMarker) {
                    temporaryMarker.setMap(null);
                    temporaryMarker = null;
                }
            }

            function clearDisplayedRoute() {
                if (directionsRenderer) {
                    directionsRenderer.set('directions', null);
                }

                removeTemporaryMarker();
            }

            function setActiveField(field) {
                activeField = field;

                startInput.classList.toggle(
                    'location-input-active',
                    field === 'start'
                );

                destinationInput.classList.toggle(
                    'location-input-active',
                    field === 'destination'
                );
            }

            function calculateFuelCost() {
                const selectedOption =
                    truckSelect.options[truckSelect.selectedIndex];

                if (!selectedOption) {
                    fuelRateInput.value = '';
                    fuelTotalInput.value = '';
                    return;
                }

                const fuelRate = Number.parseFloat(
                    selectedOption.dataset.fuelRate
                );

                const distance = Number.parseFloat(
                    distanceInput.value
                );

                const fuelPrice = Number.parseFloat(
                    fuelPriceInput.value
                );

                if (Number.isFinite(fuelRate) && fuelRate > 0) {
                    fuelRateInput.value = fuelRate;
                } else {
                    fuelRateInput.value = '';
                }

                if (
                    !Number.isFinite(distance) ||
                    distance <= 0 ||
                    !Number.isFinite(fuelPrice) ||
                    fuelPrice <= 0 ||
                    !Number.isFinite(fuelRate) ||
                    fuelRate <= 0
                ) {
                    fuelTotalInput.value = '';
                    return;
                }

                const total = (distance / fuelRate) * fuelPrice;

                fuelTotalInput.value = total.toFixed(2);
            }

            function formatCoordinates(position) {
                return [
                    position.lat().toFixed(6),
                    position.lng().toFixed(6)
                ].join(',');
            }

            function buildPlaceLabel(place) {
                const name = place.name || '';
                const address = place.formatted_address || '';

                if (!name) {
                    return address;
                }

                if (!address || address.includes(name)) {
                    return address || name;
                }

                return `${name}, ${address}`;
            }

            function updateTemporaryMarker(position, field) {
                removeTemporaryMarker();

                temporaryMarker = new google.maps.Marker({
                    map: map,
                    position: position,
                    label: field === 'start' ? 'A' : 'B',
                    title: field === 'start'
                        ? 'จุดเริ่มต้น'
                        : 'ปลายทาง'
                });
            }

            async function calculateRoute() {
                if (!directionsService || !directionsRenderer) {
                    showError(
                        'Google Maps ยังโหลดไม่เสร็จ กรุณารอสักครู่'
                    );

                    return;
                }

                const startText = startInput.value.trim();
                const destinationText = destinationInput.value.trim();

                if (!startText || !destinationText) {
                    showError(
                        'กรุณาเลือกจุดเริ่มต้นและปลายทางให้ครบ'
                    );

                    return;
                }

                clearError();
                showStatus('กำลังคำนวณเส้นทาง...');
                calculateButton.disabled = true;

                try {
                    const origin = startLocation || startText;

                    const destination =
                        destinationLocation || destinationText;

                    const result = await directionsService.route({
                        origin: origin,
                        destination: destination,
                        travelMode: google.maps.TravelMode.DRIVING,
                        region: 'TH'
                    });

                    const route = result.routes?.[0];
                    const routeLeg = route?.legs?.[0];

                    if (!routeLeg?.distance?.value) {
                        throw new Error(
                            'ไม่พบข้อมูลระยะทางของเส้นทางนี้'
                        );
                    }

                    removeTemporaryMarker();

                    directionsRenderer.setDirections(result);

                    const distanceInKilometers =
                        routeLeg.distance.value / 1000;

                    const durationInMinutes = Math.round(
                        (routeLeg.duration?.value || 0) / 60
                    );

                    distanceInput.value =
                        distanceInKilometers.toFixed(2);

                    calculateFuelCost();

                    showStatus(
                        `ระยะทาง ${distanceInKilometers.toFixed(2)} กม. ` +
                        `ใช้เวลาประมาณ ${durationInMinutes} นาที`
                    );
                } catch (error) {
                    clearRouteValues();
                    clearDisplayedRoute();

                    const errorText = String(
                        error?.code ||
                        error?.message ||
                        error
                    );

                    if (errorText.includes('ZERO_RESULTS')) {
                        showError(
                            'ไม่พบเส้นทางรถยนต์ระหว่างสถานที่ที่เลือก'
                        );
                    } else if (errorText.includes('NOT_FOUND')) {
                        showError(
                            'ไม่พบสถานที่ กรุณาเลือกจากรายการที่ Google แนะนำ'
                        );
                    } else if (
                        errorText.includes('REQUEST_DENIED') ||
                        errorText.includes('API_KEY')
                    ) {
                        showError(
                            'Google ปฏิเสธคำขอ กรุณาตรวจสอบ Billing, Directions API และ API Key'
                        );
                    } else {
                        showError(
                            'ไม่สามารถคำนวณเส้นทางได้ กรุณาลองใหม่'
                        );
                    }

                    console.error(
                        'Google Directions error:',
                        error
                    );
                } finally {
                    calculateButton.disabled = false;
                }
            }

            async function selectPlace(field, place) {
                if (!place.geometry?.location) {
                    showError(
                        'สถานที่นี้ไม่มีข้อมูลพิกัด กรุณาเลือกสถานที่ใหม่'
                    );

                    return;
                }

                clearError();

                const location = place.geometry.location;
                const label = buildPlaceLabel(place);

                if (field === 'start') {
                    startInput.value = label;
                    startLocation = location;

                    setActiveField('destination');

                    updateTemporaryMarker(
                        location,
                        'start'
                    );

                    destinationInput.focus();
                } else {
                    destinationInput.value = label;
                    destinationLocation = location;

                    updateTemporaryMarker(
                        location,
                        'destination'
                    );
                }

                map.panTo(location);
                map.setZoom(16);

                if (
                    startInput.value.trim() &&
                    destinationInput.value.trim()
                ) {
                    await calculateRoute();
                }
            }

            function createAutocomplete(input, field) {
                const autocomplete =
                    new google.maps.places.Autocomplete(
                        input,
                        {
                            componentRestrictions: {
                                country: 'th'
                            },

                            fields: [
                                'place_id',
                                'name',
                                'formatted_address',
                                'geometry'
                            ],

                            types: []
                        }
                    );

                autocomplete.bindTo('bounds', map);

                autocomplete.addListener(
                    'place_changed',
                    function () {
                        const place = autocomplete.getPlace();

                        selectPlace(
                            field,
                            place
                        );
                    }
                );

                return autocomplete;
            }

            function assignCoordinates(field, position) {
                const formattedCoordinates =
                    formatCoordinates(position);

                if (field === 'start') {
                    startInput.value = formattedCoordinates;
                    startLocation = position;

                    setActiveField('destination');
                } else {
                    destinationInput.value = formattedCoordinates;
                    destinationLocation = position;
                }

                updateTemporaryMarker(
                    position,
                    field
                );

                if (
                    startInput.value.trim() &&
                    destinationInput.value.trim()
                ) {
                    calculateRoute();
                } else {
                    showStatus(
                        field === 'start'
                            ? 'เลือกจุดเริ่มต้นแล้ว กรุณาเลือกปลายทาง'
                            : 'เลือกปลายทางแล้ว กรุณาเลือกจุดเริ่มต้น'
                    );
                }
            }

            function selectMapPlace(placeId, fallbackPosition) {
                if (!placesService) {
                    assignCoordinates(
                        activeField,
                        fallbackPosition
                    );

                    return;
                }

                const selectedField = activeField;

                placesService.getDetails(
                    {
                        placeId: placeId,

                        fields: [
                            'place_id',
                            'name',
                            'formatted_address',
                            'geometry'
                        ]
                    },
                    function (place, status) {
                        if (
                            status ===
                            google.maps.places.PlacesServiceStatus.OK
                        ) {
                            selectPlace(
                                selectedField,
                                place
                            );

                            return;
                        }

                        assignCoordinates(
                            selectedField,
                            fallbackPosition
                        );
                    }
                );
            }

            function handleMapClick(event) {
                clearError();

                if (!event.latLng) {
                    return;
                }

                if (event.placeId) {
                    event.stop();

                    selectMapPlace(
                        event.placeId,
                        event.latLng
                    );

                    return;
                }

                assignCoordinates(
                    activeField,
                    event.latLng
                );
            }

            function resetRoute() {
                startInput.value = '';
                destinationInput.value = '';

                startLocation = null;
                destinationLocation = null;

                clearRouteValues();
                clearDisplayedRoute();
                clearError();
                clearStatus();

                setActiveField('start');

                if (map) {
                    map.setCenter(defaultCenter);
                    map.setZoom(11);
                }

                startInput.focus();
            }

            function swapLocations() {
                const previousStartText =
                    startInput.value;

                startInput.value =
                    destinationInput.value;

                destinationInput.value =
                    previousStartText;

                const previousStartLocation =
                    startLocation;

                startLocation =
                    destinationLocation;

                destinationLocation =
                    previousStartLocation;

                if (
                    startInput.value.trim() &&
                    destinationInput.value.trim()
                ) {
                    calculateRoute();
                }
            }

            /*
             * ฟังก์ชันนี้ต้องอยู่ใน window
             * เพราะ Google Maps จะเรียกจาก callback ใน URL
             */
            window.initGoogleMap = function () {
                map = new google.maps.Map(
                    document.getElementById('map'),
                    {
                        center: defaultCenter,
                        zoom: 11,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: true,
                        clickableIcons: true
                    }
                );

                directionsService =
                    new google.maps.DirectionsService();

                directionsRenderer =
                    new google.maps.DirectionsRenderer({
                        map: map,
                        draggable: true,

                        polylineOptions: {
                            strokeColor: '#0d6efd',
                            strokeOpacity: 0.9,
                            strokeWeight: 5
                        }
                    });

                placesService =
                    new google.maps.places.PlacesService(map);

                startAutocomplete = createAutocomplete(
                    startInput,
                    'start'
                );

                destinationAutocomplete = createAutocomplete(
                    destinationInput,
                    'destination'
                );

                map.addListener(
                    'click',
                    handleMapClick
                );

                directionsRenderer.addListener(
                    'directions_changed',
                    function () {
                        const directions =
                            directionsRenderer.getDirections();

                        const routeLeg =
                            directions?.routes?.[0]?.legs?.[0];

                        if (!routeLeg?.distance?.value) {
                            return;
                        }

                        const distanceInKilometers =
                            routeLeg.distance.value / 1000;

                        const durationInMinutes = Math.round(
                            (routeLeg.duration?.value || 0) / 60
                        );

                        distanceInput.value =
                            distanceInKilometers.toFixed(2);

                        calculateFuelCost();

                        showStatus(
                            `ระยะทาง ${distanceInKilometers.toFixed(2)} กม. ` +
                            `ใช้เวลาประมาณ ${durationInMinutes} นาที`
                        );
                    }
                );

                setActiveField('start');

                if (
                    startInput.value.trim() &&
                    destinationInput.value.trim()
                ) {
                    calculateRoute();
                }
            };

            window.gm_authFailure = function () {
                showError(
                    'Google Maps โหลดไม่ได้ กรุณาตรวจสอบ API Key และ Website restrictions'
                );
            };

            startInput.addEventListener(
                'focus',
                function () {
                    setActiveField('start');
                }
            );

            destinationInput.addEventListener(
                'focus',
                function () {
                    setActiveField('destination');
                }
            );

            startInput.addEventListener(
                'input',
                function () {
                    startLocation = null;
                    clearRouteValues();
                }
            );

            destinationInput.addEventListener(
                'input',
                function () {
                    destinationLocation = null;
                    clearRouteValues();
                }
            );

            /*
             * ไม่บล็อก Enter ที่ช่องสถานที่
             * เพื่อให้ Google Autocomplete สามารถรับการเลือกได้
             */
            calculateButton.addEventListener(
                'click',
                calculateRoute
            );

            swapButton.addEventListener(
                'click',
                swapLocations
            );

            clearButton.addEventListener(
                'click',
                resetRoute
            );

            truckSelect.addEventListener(
                'change',
                calculateFuelCost
            );

            fuelPriceInput.addEventListener(
                'input',
                calculateFuelCost
            );

            distanceInput.addEventListener(
                'input',
                calculateFuelCost
            );

            /*
             * ป้องกันการกด Enter จากช่องอื่นแล้วบันทึกโดยไม่ตั้งใจ
             * แต่ไม่แทรกแซงช่องค้นหาสถานที่
             */
            form.addEventListener(
                'keydown',
                function (event) {
                    if (
                        event.key === 'Enter' &&
                        event.target !== startInput &&
                        event.target !== destinationInput
                    ) {
                        event.preventDefault();
                    }
                }
            );

            calculateFuelCost();
        })();
    </script>

    @if (config('services.google_maps.key'))
        <script
            async
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initGoogleMap&loading=async&language=th&region=TH"
        ></script>
    @else
        <script>
            const mapError = document.getElementById('map_error');

            mapError.textContent =
                'ยังไม่ได้ตั้งค่า GOOGLE_MAPS_API_KEY ในไฟล์ .env';

            mapError.classList.remove('d-none');
        </script>
    @endif
@endsection