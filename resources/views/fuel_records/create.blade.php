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

        .pac-container {
            z-index: 99999 !important;
            pointer-events: auto !important;
            border-radius: 0 0 8px 8px;
        }

        .pac-item {
            min-height: 45px;
            padding: 8px 12px !important;
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

        .fuel-summary {
            border: 1px solid #dbeafe;
            background-color: #f8fbff;
            border-radius: 10px;
            padding: 15px;
        }

        .fuel-summary-row {
            display: flex;
            align-items: center;
            justify-resistant: space-between;
            justify-content: space-between;
            gap: 12px;
            padding: 5px 0;
        }

        .fuel-summary-row strong {
            white-space: nowrap;
        }

        .route-option {
            width: 100%;
            text-align: left;
            border: 1px solid #dee2e6;
            background-color: #fff;
            border-radius: 10px;
            padding: 12px 14px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .route-option:hover {
            border-color: #0d6efd;
            background-color: #f5f9ff;
        }

        .route-option.active {
            border-color: #0d6efd;
            background-color: #eef5ff;
        }

        .route-option-title {
            font-weight: 600;
            color: #212529;
        }

        .route-option-details {
            color: #6c757d;
            font-size: 13px;
        }

        .route-map-label {
            position: absolute;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 1px solid #adb5bd;
            border-radius: 8px;
            padding: 7px 10px;
            min-width: 125px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            pointer-events: auto;
            white-space: nowrap;
        }

        .route-map-label.active {
            border: 2px solid #0d6efd;
            background-color: #eef5ff;
        }

        .route-map-label-title {
            font-size: 13px;
            font-weight: 700;
            color: #212529;
        }

        .route-map-label-distance {
            font-size: 12px;
            color: #6c757d;
        }
    </style>

    <div class="container py-3">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

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

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label
                            for="date_record"
                            class="form-label"
                        >
                            วันที่
                        </label>

                        <input
                            type="date"
                            id="date_record"
                            name="date_record"
                            class="form-control @error('date_record') is-invalid @enderror"
                            value="{{ old(
                                'date_record',
                                $fuel_record->date_record ?? now()->format('Y-m-d')
                            ) }}"
                            required
                        >

                        @error('date_record')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label
                            for="truck_select"
                            class="form-label"
                        >
                            รถบรรทุก
                        </label>

                        <select
                            id="truck_select"
                            name="trucks_id_truck"
                            class="form-select @error('trucks_id_truck') is-invalid @enderror"
                            required
                        >
                            @forelse ($trucks as $truck)
                                <option
                                    value="{{ $truck->id_truck }}"
                                    data-fuel-rate="{{ $truck->fuel_rate }}"
                                    data-max-load="{{ $truck->weight_truck }}"
                                    data-truck-year="{{ $truck->year_truck }}"
                                    @selected(
                                        old(
                                            'trucks_id_truck',
                                            $fuel_record->trucks_id_truck ?? ''
                                        ) == $truck->id_truck
                                    )
                                >
                                    {{ $truck->brand_truck ?? $truck->brand?->name_brand ?? 'รถบรรทุก' }}
                                    ({{ $truck->id_truck }})
                                </option>
                            @empty
                                <option value="">
                                    — ไม่มีข้อมูลรถบรรทุก —
                                </option>
                            @endforelse
                        </select>

                        @error('trucks_id_truck')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label
                            for="start_point"
                            class="form-label"
                        >
                            จุดเริ่มต้น

                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <input
                            type="text"
                            id="start_point"
                            name="start_point"
                            class="form-control @error('start_point') is-invalid @enderror"
                            placeholder="ค้นหาสถานที่ เช่น Campuslife ขอนแก่น"
                            value="{{ old(
                                'start_point',
                                $fuel_record->start_point ?? ''
                            ) }}"
                            autocomplete="off"
                            required
                        >

                        <small class="text-muted">
                            พิมพ์แล้วเลือกสถานที่จากรายการ หรือคลิกบนแผนที่
                        </small>

                        @error('start_point')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label
                            for="destination"
                            class="form-label"
                        >
                            ปลายทาง

                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <input
                            type="text"
                            id="destination"
                            name="destination"
                            class="form-control @error('destination') is-invalid @enderror"
                            placeholder="ค้นหาสถานที่ปลายทาง"
                            value="{{ old(
                                'destination',
                                $fuel_record->destination ?? ''
                            ) }}"
                            autocomplete="off"
                            required
                        >

                        <small class="text-muted">
                            ค้นหาชื่อร้านค้า มหาวิทยาลัย หอพัก หรือแคมป์งานได้
                        </small>

                        @error('destination')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3 d-flex flex-wrap gap-2">
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

                    <hr>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label
                                for="max_load_weight"
                                class="form-label"
                            >
                                น้ำหนักบรรทุกสูงสุด
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="max_load_weight"
                                    class="form-control bg-light"
                                    min="0"
                                    step="0.01"
                                    readonly
                                >

                                <span class="input-group-text">
                                    ตัน
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label
                                for="load_weight"
                                class="form-label"
                            >
                                น้ำหนักบรรทุกจริง
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="load_weight"
                                    name="load_weight"
                                    class="form-control @error('load_weight') is-invalid @enderror"
                                    min="0"
                                    step="0.01"
                                    value="{{ old(
                                        'load_weight',
                                        $fuel_record->load_weight ?? 0
                                    ) }}"
                                    required
                                >

                                <span class="input-group-text">
                                    ตัน
                                </span>

                                @error('load_weight')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <small class="text-muted">
                                กรอก 0 หากเป็นรถเปล่า
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label
                            for="load_percentage"
                            class="form-label"
                        >
                            สัดส่วนการบรรทุก
                        </label>

                        <div class="input-group">
                            <input
                                type="number"
                                id="load_percentage"
                                class="form-control bg-light"
                                min="0"
                                max="100"
                                step="0.01"
                                readonly
                            >

                            <span class="input-group-text">
                                %
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label
                            for="distance"
                            class="form-label"
                        >
                            ระยะทาง
                        </label>

                        <div class="input-group">
                            <input
                                type="number"
                                id="distance"
                                name="distance"
                                class="form-control bg-light @error('distance') is-invalid @enderror"
                                min="0"
                                step="0.01"
                                value="{{ old(
                                    'distance',
                                    $fuel_record->distance ?? ''
                                ) }}"
                                readonly
                                required
                            >

                            <span class="input-group-text">
                                กม.
                            </span>

                            @error('distance')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label
                            for="cost_fuel"
                            class="form-label"
                        >
                            ราคาน้ำมัน
                        </label>

                        <div class="input-group">
                            <input
                                type="number"
                                id="cost_fuel"
                                name="cost_fuel"
                                class="form-control bg-light @error('cost_fuel') is-invalid @enderror"
                                min="0"
                                step="0.01"
                                value="{{ old(
                                    'cost_fuel',
                                    $fuel_record->cost_fuel ?? ($dieselPrice ?? '')
                                ) }}"
                                readonly
                            >

                            <span class="input-group-text">
                                บาท/ลิตร
                            </span>

                            @error('cost_fuel')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label
                                for="show_fuel_rate"
                                class="form-label"
                            >
                                อัตราสิ้นเปลืองรถเปล่า
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="show_fuel_rate"
                                    class="form-control bg-light"
                                    min="0"
                                    step="0.01"
                                    readonly
                                >

                                <span class="input-group-text">
                                    กม./ลิตร
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label
                                for="loaded_fuel_rate"
                                class="form-label"
                            >
                                อัตราสิ้นเปลืองเที่ยวนี้
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="loaded_fuel_rate"
                                    class="form-control bg-light"
                                    min="0"
                                    step="0.01"
                                    readonly
                                >

                                <span class="input-group-text">
                                    กม./ลิตร
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="fuel-summary mb-3">
                        <div class="fw-semibold mb-2">
                            สรุปการใช้น้ำมัน
                        </div>

                        <div class="fuel-summary-row">
                            <span>
                                สถานะการบรรทุก
                            </span>

                            <strong id="load_status_display">
                                -
                            </strong>
                        </div>

                        <div class="fuel-summary-row">
                            <span>
                                น้ำหนักบรรทุก
                            </span>

                            <strong id="load_weight_display">
                                - ตัน
                            </strong>
                        </div>

                        <div class="fuel-summary-row">
                            <span>
                                สัดส่วนการบรรทุก
                            </span>

                            <strong id="load_percentage_display">
                                - %
                            </strong>
                        </div>

                        <div class="fuel-summary-row">
                            <span>
                                ระยะทาง
                            </span>

                            <strong id="distance_display">
                                - กม.
                            </strong>
                        </div>

                        <div class="fuel-summary-row">
                            <span>
                                อัตราสิ้นเปลือง
                            </span>

                            <strong id="fuel_rate_display">
                                - กม./ลิตร
                            </strong>
                        </div>

                        <hr class="my-2">

                        <div class="fuel-summary-row">
                            <span>
                                น้ำมันที่ใช้
                            </span>

                            <strong id="total_fuel_display">
                                - ลิตร
                            </strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label
                            for="cost_fuel_total"
                            class="form-label fw-semibold"
                        >
                            ค่าน้ำมัน
                        </label>

                        <div class="input-group">
                            <input
                                type="number"
                                id="cost_fuel_total"
                                name="cost_fuel_total"
                                class="form-control bg-light @error('cost_fuel_total') is-invalid @enderror"
                                min="0"
                                step="0.01"
                                value="{{ old(
                                    'cost_fuel_total',
                                    $fuel_record->cost_fuel_total ?? ''
                                ) }}"
                                readonly
                            >

                            <span class="input-group-text">
                                บาท
                            </span>

                            @error('cost_fuel_total')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button
                            type="submit"
                            class="btn btn-success"
                        >
                            บันทึก
                        </button>

                        <a
                            href="{{ route('fuel_records.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            ย้อนกลับ
                        </a>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div id="map"></div>

                    <div class="map-help mt-2">
                        คลิกช่องต้นทางหรือปลายทางก่อน จากนั้นคลิกตำแหน่งบนแผนที่
                        และสามารถคลิกเลือกเส้นทางที่ต้องการได้
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

                    <div
                        id="route_options_container"
                        class="mt-3 d-none"
                    >
                        <h6 class="fw-semibold mb-2">
                            เลือกเส้นทาง
                        </h6>

                        <div
                            id="route_options"
                            class="d-flex flex-column gap-2"
                        ></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        (() => {
            /*
             * สมมติฐาน:
             * เมื่อบรรทุกเต็มความจุ อัตรา กม./ลิตร ลดลง 20%
             *
             * ตัวอย่าง:
             * รถเปล่า 3.00 กม./ลิตร
             * บรรทุกเต็ม 100% เหลือ 2.40 กม./ลิตร
             * บรรทุก 50% เหลือ 2.70 กม./ลิตร
             * รถเปล่า 0% ยังคง 3.00 กม./ลิตร
             */
            const FULL_LOAD_EFFICIENCY_LOSS = 0.20;

            const defaultCenter = {
                lat: 16.4322,
                lng: 102.8236,
            };

            const form = document.getElementById(
                'fuel_record_form'
            );

            const startInput = document.getElementById(
                'start_point'
            );

            const destinationInput = document.getElementById(
                'destination'
            );

            const distanceInput = document.getElementById(
                'distance'
            );

            const fuelPriceInput = document.getElementById(
                'cost_fuel'
            );

            const fuelTotalInput = document.getElementById(
                'cost_fuel_total'
            );

            const fuelRateInput = document.getElementById(
                'show_fuel_rate'
            );

            const loadedFuelRateInput = document.getElementById(
                'loaded_fuel_rate'
            );

            const maximumLoadInput = document.getElementById(
                'max_load_weight'
            );

            const loadWeightInput = document.getElementById(
                'load_weight'
            );

            const loadPercentageInput = document.getElementById(
                'load_percentage'
            );

            const truckSelect = document.getElementById(
                'truck_select'
            );

            const calculateButton = document.getElementById(
                'calculate_route_button'
            );

            const swapButton = document.getElementById(
                'swap_locations_button'
            );

            const clearButton = document.getElementById(
                'clear_route_button'
            );

            const statusElement = document.getElementById(
                'map_status'
            );

            const errorElement = document.getElementById(
                'map_error'
            );

            const routeOptionsContainer = document.getElementById(
                'route_options_container'
            );

            const routeOptionsElement = document.getElementById(
                'route_options'
            );

            const loadStatusDisplay = document.getElementById(
                'load_status_display'
            );

            const loadWeightDisplay = document.getElementById(
                'load_weight_display'
            );

            const loadPercentageDisplay = document.getElementById(
                'load_percentage_display'
            );

            const distanceDisplay = document.getElementById(
                'distance_display'
            );

            const fuelRateDisplay = document.getElementById(
                'fuel_rate_display'
            );

            const totalFuelDisplay = document.getElementById(
                'total_fuel_display'
            );

            let map = null;

            let directionsService = null;

            let placesService = null;

            let startMarker = null;

            let destinationMarker = null;

            let startLocation = null;

            let destinationLocation = null;

            let activeField = 'start';

            let currentRoutes = [];

            let routePolylines = [];

            let routeLabels = [];

            let selectedRouteIndex = 0;

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

            function getSelectedTruckInformation() {
                const selectedOption =
                    truckSelect.options[
                        truckSelect.selectedIndex
                    ];

                if (
                    !selectedOption ||
                    !selectedOption.value
                ) {
                    return null;
                }

                const fuelRate = Number.parseFloat(
                    selectedOption.dataset.fuelRate
                );

                const maximumLoad = Number.parseFloat(
                    selectedOption.dataset.maxLoad
                );

                return {
                    fuelRate,
                    maximumLoad,
                    selectedOption,
                };
            }

            function updateTruckInformation() {
                const truckInformation =
                    getSelectedTruckInformation();

                if (!truckInformation) {
                    maximumLoadInput.value = '';

                    fuelRateInput.value = '';

                    loadWeightInput.removeAttribute('max');

                    return;
                }

                const maximumLoad =
                    truckInformation.maximumLoad;

                const fuelRate =
                    truckInformation.fuelRate;

                maximumLoadInput.value =
                    Number.isFinite(maximumLoad) &&
                    maximumLoad > 0
                        ? maximumLoad.toFixed(2)
                        : '';

                fuelRateInput.value =
                    Number.isFinite(fuelRate) &&
                    fuelRate > 0
                        ? fuelRate.toFixed(2)
                        : '';

                if (
                    Number.isFinite(maximumLoad) &&
                    maximumLoad > 0
                ) {
                    loadWeightInput.max =
                        String(maximumLoad);
                } else {
                    loadWeightInput.removeAttribute('max');
                }
            }

            function getLoadInformation() {
                const truckInformation =
                    getSelectedTruckInformation();

                if (!truckInformation) {
                    throw new Error(
                        'กรุณาเลือกรถบรรทุก'
                    );
                }

                const emptyFuelRate =
                    truckInformation.fuelRate;

                if (
                    !Number.isFinite(emptyFuelRate) ||
                    emptyFuelRate <= 0
                ) {
                    throw new Error(
                        'รถคันนี้ไม่มีข้อมูลอัตราสิ้นเปลือง'
                    );
                }

                const maximumLoad =
                    truckInformation.maximumLoad;

                if (
                    !Number.isFinite(maximumLoad) ||
                    maximumLoad <= 0
                ) {
                    throw new Error(
                        'รถคันนี้ไม่มีข้อมูลน้ำหนักบรรทุกสูงสุด'
                    );
                }

                const loadWeight = Number.parseFloat(
                    loadWeightInput.value || '0'
                );

                if (
                    !Number.isFinite(loadWeight) ||
                    loadWeight < 0
                ) {
                    throw new Error(
                        'กรุณากรอกน้ำหนักบรรทุกให้ถูกต้อง'
                    );
                }

                if (loadWeight > maximumLoad) {
                    throw new Error(
                        `น้ำหนักบรรทุกต้องไม่เกิน ${maximumLoad.toFixed(2)} ตัน`
                    );
                }

                const loadRatio =
                    loadWeight / maximumLoad;

                const actualFuelRate =
                    emptyFuelRate *
                    (
                        1 -
                        (
                            loadRatio *
                            FULL_LOAD_EFFICIENCY_LOSS
                        )
                    );

                if (
                    !Number.isFinite(actualFuelRate) ||
                    actualFuelRate <= 0
                ) {
                    throw new Error(
                        'ไม่สามารถคำนวณอัตราสิ้นเปลืองได้'
                    );
                }

                return {
                    maximumLoad,
                    loadWeight,
                    loadRatio,
                    emptyFuelRate,
                    actualFuelRate,
                };
            }

            function getTripFuelInformation(distance) {
                const loadInformation =
                    getLoadInformation();

                if (
                    !Number.isFinite(distance) ||
                    distance <= 0
                ) {
                    throw new Error(
                        'กรุณาคำนวณเส้นทางก่อน'
                    );
                }

                const fuelPrice = Number.parseFloat(
                    fuelPriceInput.value
                );

                if (
                    !Number.isFinite(fuelPrice) ||
                    fuelPrice <= 0
                ) {
                    throw new Error(
                        'กรุณาตรวจสอบราคาน้ำมัน'
                    );
                }

                const fuelLiters =
                    distance /
                    loadInformation.actualFuelRate;

                const totalFuelCost =
                    fuelLiters *
                    fuelPrice;

                return {
                    ...loadInformation,

                    distance,

                    fuelPrice,

                    fuelLiters,

                    totalFuelCost,
                };
            }

            function clearFuelSummary() {
                loadStatusDisplay.textContent =
                    '-';

                loadWeightDisplay.textContent =
                    '- ตัน';

                loadPercentageDisplay.textContent =
                    '- %';

                distanceDisplay.textContent =
                    '- กม.';

                fuelRateDisplay.textContent =
                    '- กม./ลิตร';

                totalFuelDisplay.textContent =
                    '- ลิตร';
            }

            function clearCalculatedValues() {
                distanceInput.value = '';

                fuelTotalInput.value = '';

                clearFuelSummary();
            }

            function updateLoadPreview() {
                try {
                    const loadInformation =
                        getLoadInformation();

                    loadPercentageInput.value =
                        (
                            loadInformation.loadRatio * 100
                        ).toFixed(2);

                    loadedFuelRateInput.value =
                        loadInformation.actualFuelRate.toFixed(2);

                    return true;
                } catch (error) {
                    loadPercentageInput.value = '';

                    loadedFuelRateInput.value = '';

                    return false;
                }
            }

            function calculateFuelCost() {
                updateTruckInformation();

                updateLoadPreview();

                const distance = Number.parseFloat(
                    distanceInput.value
                );

                if (
                    !Number.isFinite(distance) ||
                    distance <= 0
                ) {
                    fuelTotalInput.value = '';

                    clearFuelSummary();

                    return;
                }

                try {
                    const information =
                        getTripFuelInformation(
                            distance
                        );

                    clearError();

                    maximumLoadInput.value =
                        information.maximumLoad.toFixed(2);

                    fuelRateInput.value =
                        information.emptyFuelRate.toFixed(2);

                    loadPercentageInput.value =
                        (
                            information.loadRatio * 100
                        ).toFixed(2);

                    loadedFuelRateInput.value =
                        information.actualFuelRate.toFixed(2);

                    fuelTotalInput.value =
                        information.totalFuelCost.toFixed(2);

                    loadStatusDisplay.textContent =
                        information.loadWeight > 0
                            ? 'บรรทุก'
                            : 'รถเปล่า';

                    loadWeightDisplay.textContent =
                        `${information.loadWeight.toFixed(2)} ตัน`;

                    loadPercentageDisplay.textContent =
                        `${(information.loadRatio * 100).toFixed(2)} %`;

                    distanceDisplay.textContent =
                        `${information.distance.toFixed(2)} กม.`;

                    fuelRateDisplay.textContent =
                        `${information.actualFuelRate.toFixed(2)} กม./ลิตร`;

                    totalFuelDisplay.textContent =
                        `${information.fuelLiters.toFixed(2)} ลิตร`;
                } catch (error) {
                    fuelTotalInput.value = '';

                    clearFuelSummary();

                    showError(
                        error.message ||
                        'ไม่สามารถคำนวณค่าน้ำมันได้'
                    );
                }
            }

            function calculateEstimatedCost(distance) {
                try {
                    const information =
                        getTripFuelInformation(
                            distance
                        );

                    return information.totalFuelCost;
                } catch (error) {
                    return null;
                }
            }

            function formatCoordinates(position) {
                return [
                    position.lat().toFixed(6),

                    position.lng().toFixed(6),
                ].join(',');
            }

            function buildPlaceLabel(place) {
                const name =
                    place.name || '';

                const address =
                    place.formatted_address || '';

                if (!name) {
                    return address;
                }

                if (
                    !address ||
                    address.includes(name)
                ) {
                    return address || name;
                }

                return `${name}, ${address}`;
            }

            function removeStartMarker() {
                if (startMarker) {
                    startMarker.setMap(null);

                    startMarker = null;
                }
            }

            function removeDestinationMarker() {
                if (destinationMarker) {
                    destinationMarker.setMap(null);

                    destinationMarker = null;
                }
            }

            function removeMarkers() {
                removeStartMarker();

                removeDestinationMarker();
            }

            function setMarker(
                field,
                position
            ) {
                if (!map) {
                    return;
                }

                if (field === 'start') {
                    removeStartMarker();

                    startMarker =
                        new google.maps.Marker({
                            map,

                            position,

                            label: 'A',

                            title:
                                'จุดเริ่มต้น',
                        });

                    return;
                }

                removeDestinationMarker();

                destinationMarker =
                    new google.maps.Marker({
                        map,

                        position,

                        label: 'B',

                        title:
                            'ปลายทาง',
                    });
            }

            function getRouteLeg(route) {
                return route?.legs?.[0] || null;
            }

            function getRouteDistance(route) {
                const leg =
                    getRouteLeg(route);

                if (!leg?.distance?.value) {
                    return 0;
                }

                return leg.distance.value / 1000;
            }

            function getRouteDuration(route) {
                const leg =
                    getRouteLeg(route);

                if (!leg?.duration?.value) {
                    return 0;
                }

                return Math.round(
                    leg.duration.value / 60
                );
            }

            function formatDuration(totalMinutes) {
                if (
                    !Number.isFinite(totalMinutes) ||
                    totalMinutes <= 0
                ) {
                    return '-';
                }

                const hours =
                    Math.floor(
                        totalMinutes / 60
                    );

                const minutes =
                    totalMinutes % 60;

                if (hours <= 0) {
                    return `${minutes} นาที`;
                }

                if (minutes === 0) {
                    return `${hours} ชม.`;
                }

                return `${hours} ชม. ${minutes} นาที`;
            }

            function getRouteSummary(
                route,
                index
            ) {
                const routeSummary =
                    route?.summary?.trim();

                if (routeSummary) {
                    return routeSummary;
                }

                return `เส้นทางที่ ${index + 1}`;
            }

            function getRoutePath(route) {
                if (
                    Array.isArray(
                        route?.overview_path
                    ) &&
                    route.overview_path.length
                ) {
                    return route.overview_path;
                }

                const path = [];

                const legs =
                    route?.legs || [];

                legs.forEach(
                    function (leg) {
                        (
                            leg.steps || []
                        ).forEach(
                            function (step) {
                                (
                                    step.path || []
                                ).forEach(
                                    function (point) {
                                        path.push(
                                            point
                                        );
                                    }
                                );
                            }
                        );
                    }
                );

                return path;
            }

            function getRouteLabelPosition(
                route,
                index
            ) {
                const path =
                    getRoutePath(route);

                if (!path.length) {
                    return null;
                }

                const fractions = [
                    0.45,

                    0.60,

                    0.30,

                    0.72,

                    0.18,
                ];

                const fraction =
                    fractions[
                        index %
                        fractions.length
                    ];

                const pathIndex =
                    Math.min(
                        path.length - 1,

                        Math.floor(
                            path.length *
                            fraction
                        )
                    );

                return path[pathIndex];
            }

            function removeRoutePolylines() {
                routePolylines.forEach(
                    function (polyline) {
                        polyline.setMap(null);
                    }
                );

                routePolylines = [];
            }

            function removeRouteLabels() {
                routeLabels.forEach(
                    function (label) {
                        label.setMap(null);
                    }
                );

                routeLabels = [];
            }

            function clearRouteOptions() {
                routeOptionsElement.innerHTML =
                    '';

                routeOptionsContainer.classList.add(
                    'd-none'
                );
            }

            function clearDisplayedRoutes() {
                removeRoutePolylines();

                removeRouteLabels();

                clearRouteOptions();

                currentRoutes = [];

                selectedRouteIndex = 0;
            }

            function getRouteLineStyle(index) {
                const isSelected =
                    index ===
                    selectedRouteIndex;

                return {
                    strokeColor:
                        isSelected
                            ? '#0d6efd'
                            : '#8b96a5',

                    strokeOpacity:
                        isSelected
                            ? 0.95
                            : 0.75,

                    strokeWeight:
                        isSelected
                            ? 7
                            : 5,

                    zIndex:
                        isSelected
                            ? 100
                            : 10,

                    clickable:
                        true,
                };
            }

            function createRouteLabel(
                route,
                index
            ) {
                const position =
                    getRouteLabelPosition(
                        route,

                        index
                    );

                if (!position) {
                    return null;
                }

                class RouteLabelOverlay
                    extends google.maps.OverlayView {
                    constructor(
                        labelPosition,

                        routeIndex
                    ) {
                        super();

                        this.position =
                            labelPosition;

                        this.routeIndex =
                            routeIndex;

                        this.element =
                            null;
                    }

                    onAdd() {
                        this.element =
                            document.createElement(
                                'div'
                            );

                        this.element.className =
                            'route-map-label';

                        this.element.addEventListener(
                            'click',

                            (event) => {
                                event.preventDefault();

                                event.stopPropagation();

                                selectRoute(
                                    this.routeIndex
                                );
                            }
                        );

                        this.updateContent();

                        const panes =
                            this.getPanes();

                        panes.overlayMouseTarget.appendChild(
                            this.element
                        );
                    }

                    draw() {
                        if (!this.element) {
                            return;
                        }

                        const projection =
                            this.getProjection();

                        if (!projection) {
                            return;
                        }

                        const pixelPosition =
                            projection.fromLatLngToDivPixel(
                                this.position
                            );

                        if (!pixelPosition) {
                            return;
                        }

                        this.element.style.left =
                            `${pixelPosition.x}px`;

                        this.element.style.top =
                            `${pixelPosition.y}px`;
                    }

                    onRemove() {
                        if (this.element) {
                            this.element.remove();

                            this.element =
                                null;
                        }
                    }

                    updateContent() {
                        if (!this.element) {
                            return;
                        }

                        const selectedRoute =
                            currentRoutes[
                                this.routeIndex
                            ];

                        if (!selectedRoute) {
                            return;
                        }

                        const routeDistance =
                            getRouteDistance(
                                selectedRoute
                            );

                        const routeDuration =
                            getRouteDuration(
                                selectedRoute
                            );

                        const title =
                            document.createElement(
                                'div'
                            );

                        title.className =
                            'route-map-label-title';

                        title.textContent =
                            formatDuration(
                                routeDuration
                            );

                        const distance =
                            document.createElement(
                                'div'
                            );

                        distance.className =
                            'route-map-label-distance';

                        distance.textContent =
                            `${routeDistance.toFixed(2)} กม.`;

                        this.element.replaceChildren(
                            title,

                            distance
                        );

                        this.element.classList.toggle(
                            'active',

                            this.routeIndex ===
                                selectedRouteIndex
                        );
                    }
                }

                const overlay =
                    new RouteLabelOverlay(
                        position,

                        index
                    );

                overlay.setMap(
                    map
                );

                return overlay;
            }

            function createRoutePolylines() {
                removeRoutePolylines();

                removeRouteLabels();

                currentRoutes.forEach(
                    function (
                        route,

                        index
                    ) {
                        const path =
                            getRoutePath(
                                route
                            );

                        if (!path.length) {
                            return;
                        }

                        const polyline =
                            new google.maps.Polyline({
                                map,

                                path,

                                ...getRouteLineStyle(
                                    index
                                ),
                            });

                        polyline.routeIndex =
                            index;

                        polyline.addListener(
                            'click',

                            function () {
                                selectRoute(
                                    index
                                );
                            }
                        );

                        routePolylines.push(
                            polyline
                        );

                        const routeLabel =
                            createRouteLabel(
                                route,

                                index
                            );

                        if (routeLabel) {
                            routeLabels.push(
                                routeLabel
                            );
                        }
                    }
                );
            }

            function refreshRouteStyles() {
                routePolylines.forEach(
                    function (polyline) {
                        polyline.setOptions(
                            getRouteLineStyle(
                                polyline.routeIndex
                            )
                        );
                    }
                );

                routeLabels.forEach(
                    function (label) {
                        label.updateContent();
                    }
                );

                routeOptionsElement
                    .querySelectorAll(
                        '[data-route-index]'
                    )
                    .forEach(
                        function (button) {
                            button.classList.toggle(
                                'active',

                                Number(
                                    button.dataset.routeIndex
                                ) ===
                                    selectedRouteIndex
                            );
                        }
                    );
            }

            function createRouteOptionButton(
                route,

                index
            ) {
                const routeDistance =
                    getRouteDistance(
                        route
                    );

                const routeDuration =
                    getRouteDuration(
                        route
                    );

                const estimatedCost =
                    calculateEstimatedCost(
                        routeDistance
                    );

                const button =
                    document.createElement(
                        'button'
                    );

                button.type =
                    'button';

                button.className =
                    'route-option';

                button.dataset.routeIndex =
                    String(index);

                button.classList.toggle(
                    'active',

                    index ===
                        selectedRouteIndex
                );

                const title =
                    document.createElement(
                        'div'
                    );

                title.className =
                    'route-option-title';

                title.textContent =
                    `${getRouteSummary(route, index)} — ${formatDuration(routeDuration)}`;

                const details =
                    document.createElement(
                        'div'
                    );

                details.className =
                    'route-option-details';

                const detailParts = [
                    `ระยะทาง ${routeDistance.toFixed(2)} กม.`,
                ];

                if (
                    Number.isFinite(
                        estimatedCost
                    )
                ) {
                    detailParts.push(
                        `ค่าน้ำมันประมาณ ${estimatedCost.toFixed(2)} บาท`
                    );
                }

                details.textContent =
                    detailParts.join(
                        ' • '
                    );

                button.appendChild(
                    title
                );

                button.appendChild(
                    details
                );

                button.addEventListener(
                    'click',

                    function () {
                        selectRoute(
                            index
                        );
                    }
                );

                return button;
            }

            function refreshRouteOptions() {
                routeOptionsElement.innerHTML =
                    '';

                if (!currentRoutes.length) {
                    routeOptionsContainer.classList.add(
                        'd-none'
                    );

                    return;
                }

                currentRoutes.forEach(
                    function (
                        route,

                        index
                    ) {
                        routeOptionsElement.appendChild(
                            createRouteOptionButton(
                                route,

                                index
                            )
                        );
                    }
                );

                routeOptionsContainer.classList.remove(
                    'd-none'
                );
            }

            function fitRoutesToMap() {
                if (
                    !map ||
                    !currentRoutes.length
                ) {
                    return;
                }

                const bounds =
                    new google.maps.LatLngBounds();

                let hasPoints =
                    false;

                currentRoutes.forEach(
                    function (route) {
                        const path =
                            getRoutePath(
                                route
                            );

                        path.forEach(
                            function (position) {
                                bounds.extend(
                                    position
                                );

                                hasPoints =
                                    true;
                            }
                        );
                    }
                );

                if (hasPoints) {
                    map.fitBounds(
                        bounds,

                        60
                    );
                }
            }

            function updateRouteMarkers(route) {
                const leg =
                    getRouteLeg(
                        route
                    );

                if (!leg) {
                    return;
                }

                if (leg.start_location) {
                    setMarker(
                        'start',

                        leg.start_location
                    );
                }

                if (leg.end_location) {
                    setMarker(
                        'destination',

                        leg.end_location
                    );
                }
            }

            function selectRoute(index) {
                const route =
                    currentRoutes[
                        index
                    ];

                if (!route) {
                    return;
                }

                selectedRouteIndex =
                    index;

                const routeDistance =
                    getRouteDistance(
                        route
                    );

                const routeDuration =
                    getRouteDuration(
                        route
                    );

                if (routeDistance <= 0) {
                    showError(
                        'ไม่พบข้อมูลระยะทางของเส้นทางนี้'
                    );

                    return;
                }

                distanceInput.value =
                    routeDistance.toFixed(
                        2
                    );

                updateRouteMarkers(
                    route
                );

                refreshRouteStyles();

                calculateFuelCost();

                refreshRouteOptions();

                if (
                    !errorElement.classList.contains(
                        'd-none'
                    )
                ) {
                    return;
                }

                showStatus(
                    `เลือกเส้นทาง ${getRouteSummary(route, index)} ระยะทาง ${routeDistance.toFixed(2)} กม. ใช้เวลาประมาณ ${formatDuration(routeDuration)}`
                );
            }

            async function calculateRoute() {
                if (
                    !map ||
                    !directionsService
                ) {
                    showError(
                        'Google Maps ยังโหลดไม่เสร็จ กรุณารอสักครู่'
                    );

                    return;
                }

                const startText =
                    startInput.value.trim();

                const destinationText =
                    destinationInput.value.trim();

                if (
                    !startText ||
                    !destinationText
                ) {
                    showError(
                        'กรุณาเลือกจุดเริ่มต้นและปลายทางให้ครบ'
                    );

                    return;
                }

                clearError();

                showStatus(
                    'กำลังค้นหาเส้นทางที่สามารถเลือกได้...'
                );

                calculateButton.disabled =
                    true;

                try {
                    const origin =
                        startLocation ||
                        startText;

                    const destination =
                        destinationLocation ||
                        destinationText;

                    const result =
                        await directionsService.route({
                            origin,

                            destination,

                            travelMode:
                                google.maps.TravelMode.DRIVING,

                            region:
                                'TH',

                            provideRouteAlternatives:
                                true,

                            avoidFerries:
                                true,
                        });

                    if (
                        !Array.isArray(
                            result.routes
                        ) ||
                        result.routes.length === 0
                    ) {
                        throw new Error(
                            'ไม่พบเส้นทางรถยนต์'
                        );
                    }

                    clearDisplayedRoutes();

                    currentRoutes =
                        result.routes;

                    selectedRouteIndex =
                        0;

                    createRoutePolylines();

                    refreshRouteOptions();

                    fitRoutesToMap();

                    selectRoute(
                        0
                    );
                } catch (error) {
                    clearCalculatedValues();

                    clearDisplayedRoutes();

                    const errorText =
                        String(
                            error?.code ||
                            error?.message ||
                            error
                        );

                    if (
                        errorText.includes(
                            'ZERO_RESULTS'
                        )
                    ) {
                        showError(
                            'ไม่พบเส้นทางรถยนต์ระหว่างสถานที่ที่เลือก'
                        );
                    } else if (
                        errorText.includes(
                            'NOT_FOUND'
                        )
                    ) {
                        showError(
                            'ไม่พบสถานที่ กรุณาเลือกสถานที่จากรายการที่ Google แนะนำ'
                        );
                    } else if (
                        errorText.includes(
                            'REQUEST_DENIED'
                        ) ||
                        errorText.includes(
                            'API_KEY'
                        )
                    ) {
                        showError(
                            'Google ปฏิเสธคำขอ กรุณาตรวจสอบ Billing, Directions API, Places API และ API Key'
                        );
                    } else {
                        showError(
                            error.message ||
                            'ไม่สามารถคำนวณเส้นทางได้ กรุณาลองใหม่'
                        );
                    }

                    console.error(
                        'Google Directions error:',

                        error
                    );
                } finally {
                    calculateButton.disabled =
                        false;
                }
            }

            async function selectPlace(
                field,

                place
            ) {
                if (
                    !place?.geometry?.location
                ) {
                    showError(
                        'สถานที่นี้ไม่มีข้อมูลพิกัด กรุณาเลือกสถานที่ใหม่'
                    );

                    return;
                }

                clearError();

                const location =
                    place.geometry.location;

                const label =
                    buildPlaceLabel(
                        place
                    );

                clearDisplayedRoutes();

                clearCalculatedValues();

                if (
                    field === 'start'
                ) {
                    startInput.value =
                        label;

                    startLocation =
                        location;

                    setMarker(
                        'start',

                        location
                    );

                    setActiveField(
                        'destination'
                    );
                } else {
                    destinationInput.value =
                        label;

                    destinationLocation =
                        location;

                    setMarker(
                        'destination',

                        location
                    );

                    setActiveField(
                        'destination'
                    );
                }

                map.panTo(
                    location
                );

                map.setZoom(
                    15
                );

                if (
                    startInput.value.trim() &&
                    destinationInput.value.trim()
                ) {
                    await calculateRoute();
                } else {
                    showStatus(
                        field === 'start'
                            ? 'เลือกจุดเริ่มต้นแล้ว กรุณาเลือกปลายทาง'
                            : 'เลือกปลายทางแล้ว กรุณาเลือกจุดเริ่มต้น'
                    );
                }
            }

            function createAutocomplete(
                input,

                field
            ) {
                const autocomplete =
                    new google.maps.places.Autocomplete(
                        input,

                        {
                            componentRestrictions: {
                                country:
                                    'th',
                            },

                            fields: [
                                'place_id',

                                'name',

                                'formatted_address',

                                'geometry',
                            ],
                        }
                    );

                autocomplete.bindTo(
                    'bounds',

                    map
                );

                autocomplete.addListener(
                    'place_changed',

                    function () {
                        const place =
                            autocomplete.getPlace();

                        selectPlace(
                            field,

                            place
                        );
                    }
                );

                return autocomplete;
            }

            function assignCoordinates(
                field,

                position
            ) {
                clearDisplayedRoutes();

                clearCalculatedValues();

                const formattedCoordinates =
                    formatCoordinates(
                        position
                    );

                if (
                    field === 'start'
                ) {
                    startInput.value =
                        formattedCoordinates;

                    startLocation =
                        position;

                    setMarker(
                        'start',

                        position
                    );

                    setActiveField(
                        'destination'
                    );
                } else {
                    destinationInput.value =
                        formattedCoordinates;

                    destinationLocation =
                        position;

                    setMarker(
                        'destination',

                        position
                    );
                }

                if (
                    startInput.value.trim() &&
                    destinationInput.value.trim()
                ) {
                    calculateRoute();

                    return;
                }

                showStatus(
                    field === 'start'
                        ? 'เลือกจุดเริ่มต้นแล้ว กรุณาเลือกปลายทาง'
                        : 'เลือกปลายทางแล้ว กรุณาเลือกจุดเริ่มต้น'
                );
            }

            function selectMapPlace(
                placeId,

                fallbackPosition
            ) {
                if (
                    !placesService
                ) {
                    assignCoordinates(
                        activeField,

                        fallbackPosition
                    );

                    return;
                }

                const selectedField =
                    activeField;

                placesService.getDetails(
                    {
                        placeId,

                        fields: [
                            'place_id',

                            'name',

                            'formatted_address',

                            'geometry',
                        ],
                    },

                    function (
                        place,

                        status
                    ) {
                        if (
                            status ===
                                google.maps.places.PlacesServiceStatus.OK &&
                            place?.geometry?.location
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

                if (
                    !event.latLng
                ) {
                    return;
                }

                if (
                    event.placeId
                ) {
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
                startInput.value =
                    '';

                destinationInput.value =
                    '';

                startLocation =
                    null;

                destinationLocation =
                    null;

                clearCalculatedValues();

                clearDisplayedRoutes();

                removeMarkers();

                clearError();

                clearStatus();

                setActiveField(
                    'start'
                );

                if (
                    map
                ) {
                    map.setCenter(
                        defaultCenter
                    );

                    map.setZoom(
                        11
                    );
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

                clearDisplayedRoutes();

                clearCalculatedValues();

                removeMarkers();

                clearError();

                clearStatus();

                if (
                    startLocation
                ) {
                    setMarker(
                        'start',

                        startLocation
                    );
                }

                if (
                    destinationLocation
                ) {
                    setMarker(
                        'destination',

                        destinationLocation
                    );
                }

                if (
                    startInput.value.trim() &&
                    destinationInput.value.trim()
                ) {
                    calculateRoute();
                }
            }

            function invalidateRouteAfterInput(field) {
                if (
                    field === 'start'
                ) {
                    startLocation =
                        null;

                    removeStartMarker();
                } else {
                    destinationLocation =
                        null;

                    removeDestinationMarker();
                }

                clearDisplayedRoutes();

                clearCalculatedValues();

                clearError();

                clearStatus();
            }

            window.initGoogleMap =
                function () {
                    map =
                        new google.maps.Map(
                            document.getElementById(
                                'map'
                            ),

                            {
                                center:
                                    defaultCenter,

                                zoom:
                                    11,

                                mapTypeControl:
                                    false,

                                streetViewControl:
                                    false,

                                fullscreenControl:
                                    true,

                                clickableIcons:
                                    true,
                            }
                        );

                    directionsService =
                        new google.maps.DirectionsService();

                    if (
                        google.maps.places
                    ) {
                        placesService =
                            new google.maps.places.PlacesService(
                                map
                            );

                        createAutocomplete(
                            startInput,

                            'start'
                        );

                        createAutocomplete(
                            destinationInput,

                            'destination'
                        );
                    } else {
                        showError(
                            'Google Places โหลดไม่สำเร็จ กรุณาตรวจสอบ Places API'
                        );
                    }

                    map.addListener(
                        'click',

                        handleMapClick
                    );

                    setActiveField(
                        'start'
                    );

                    if (
                        startInput.value.trim() &&
                        destinationInput.value.trim()
                    ) {
                        calculateRoute();
                    }
                };

            window.gm_authFailure =
                function () {
                    showError(
                        'Google Maps โหลดไม่ได้ กรุณาตรวจสอบ API Key, Billing และ Website restrictions'
                    );
                };

            startInput.addEventListener(
                'focus',

                function () {
                    setActiveField(
                        'start'
                    );
                }
            );

            destinationInput.addEventListener(
                'focus',

                function () {
                    setActiveField(
                        'destination'
                    );
                }
            );

            startInput.addEventListener(
                'input',

                function () {
                    invalidateRouteAfterInput(
                        'start'
                    );
                }
            );

            destinationInput.addEventListener(
                'input',

                function () {
                    invalidateRouteAfterInput(
                        'destination'
                    );
                }
            );

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

                function () {
                    calculateFuelCost();

                    refreshRouteOptions();
                }
            );

            loadWeightInput.addEventListener(
                'input',

                function () {
                    const truckInformation =
                        getSelectedTruckInformation();

                    const maximumLoad =
                        truckInformation?.maximumLoad;

                    const loadWeight = Number.parseFloat(
                        loadWeightInput.value || '0'
                    );

                    if (
                        Number.isFinite(maximumLoad) &&
                        Number.isFinite(loadWeight) &&
                        loadWeight > maximumLoad
                    ) {
                        showError(
                            `น้ำหนักบรรทุกต้องไม่เกิน ${maximumLoad.toFixed(2)} ตัน`
                        );

                        loadPercentageInput.value = '';

                        loadedFuelRateInput.value = '';

                        fuelTotalInput.value = '';

                        clearFuelSummary();

                        refreshRouteOptions();

                        return;
                    }

                    clearError();

                    calculateFuelCost();

                    refreshRouteOptions();
                }
            );

            fuelPriceInput.addEventListener(
                'input',

                function () {
                    calculateFuelCost();

                    refreshRouteOptions();
                }
            );

            distanceInput.addEventListener(
                'input',

                calculateFuelCost
            );

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

            form.addEventListener(
                'submit',

                function (event) {
                    const distance = Number.parseFloat(
                        distanceInput.value
                    );

                    try {
                        getTripFuelInformation(
                            distance
                        );
                    } catch (error) {
                        event.preventDefault();

                        showError(
                            error.message ||
                            'กรุณาตรวจสอบข้อมูลก่อนบันทึก'
                        );
                    }
                }
            );

            updateTruckInformation();

            updateLoadPreview();

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
            const mapError =
                document.getElementById(
                    'map_error'
                );

            mapError.textContent =
                'ยังไม่ได้ตั้งค่า GOOGLE_MAPS_API_KEY ในไฟล์ .env';

            mapError.classList.remove(
                'd-none'
            );
        </script>
    @endif
@endsection