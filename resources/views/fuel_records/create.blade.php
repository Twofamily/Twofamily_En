@extends('layout')

@section('namepage')
    <div class="container">
        <h3>
            {{ isset($fuel_record) ? 'แก้ไขบันทึกน้ำมัน' : 'เพิ่มบันทึกน้ำมัน' }}
        </h3>
    </div>
@endsection

@section('content')
    @php
        $oldSegments = old('segments');

        if (is_array($oldSegments) && count($oldSegments) > 0) {
            $initialSegments = collect($oldSegments)
                ->values()
                ->map(function ($segment) {
                    return [
                        'destination' => $segment['destination'] ?? '',
                        'load_weight' => $segment['load_weight'] ?? 0,
                        'distance' => $segment['distance'] ?? '',
                        'fuel_rate' => $segment['fuel_rate'] ?? '',
                        'fuel_liters' => $segment['fuel_liters'] ?? '',
                        'fuel_cost' => $segment['fuel_cost'] ?? '',
                    ];
                })
                ->all();
        } elseif (
            isset($fuel_record)
            && isset($fuel_record->segments)
            && $fuel_record->segments->count() > 0
        ) {
            $initialSegments = $fuel_record->segments
                ->sortBy('sequence')
                ->values()
                ->map(function ($segment) {
                    return [
                        'destination' => $segment->destination,
                        'load_weight' => $segment->load_weight,
                        'distance' => $segment->distance,
                        'fuel_rate' => $segment->fuel_rate,
                        'fuel_liters' => $segment->fuel_liters,
                        'fuel_cost' => $segment->fuel_cost,
                    ];
                })
                ->all();
        } else {
            $initialSegments = [
                [
                    'destination' => old(
                        'destination',
                        $fuel_record->destination ?? ''
                    ),

                    'load_weight' => old(
                        'load_weight',
                        $fuel_record->current_weight ?? 0
                    ),

                    'distance' => old(
                        'distance',
                        $fuel_record->distance ?? ''
                    ),

                    'fuel_rate' => '',

                    'fuel_liters' => '',

                    'fuel_cost' => old(
                        'cost_fuel_total',
                        $fuel_record->cost_fuel_total ?? ''
                    ),
                ],
            ];
        }
    @endphp

    <style>
        #map {
            width: 100%;
            height: 600px;
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

        .segment-card {
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 15px;
            background-color: #fff;
            transition: 0.2s ease;
        }

        .segment-card.active {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.10);
        }

        .segment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .segment-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .segment-color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex: 0 0 12px;
        }

        .segment-origin-box {
            background-color: #f8f9fa;
            border: 1px dashed #ced4da;
            border-radius: 8px;
            padding: 9px 12px;
            color: #495057;
            font-size: 14px;
            min-height: 42px;
        }

        .segment-summary {
            background-color: #f8fbff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 12px;
        }

        .segment-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 3px 0;
            font-size: 14px;
        }

        .segment-summary-row strong {
            white-space: nowrap;
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
            justify-content: space-between;
            gap: 12px;
            padding: 5px 0;
        }

        .fuel-summary-row strong {
            white-space: nowrap;
        }

        .route-options-box {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 10px;
            background-color: #fff;
            margin-top: 12px;
        }

        .route-option {
            width: 100%;
            text-align: left;
            border: 1px solid #dee2e6;
            background-color: #fff;
            border-radius: 8px;
            padding: 10px 12px;
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
            font-size: 14px;
        }

        .route-option-details {
            color: #6c757d;
            font-size: 13px;
        }

        .map-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .map-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #dee2e6;
            border-radius: 999px;
            padding: 5px 10px;
            background-color: #fff;
            font-size: 13px;
        }

        .map-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
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
                                isset($fuel_record)
                                    ? $fuel_record->date_record?->format('Y-m-d')
                                    : now()->format('Y-m-d')
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
                                    data-purchase-year="{{ $truck->year_truck }}"
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
                                    step="0.01"
                                    readonly
                                >

                                <span class="input-group-text">
                                    กก.
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label
                                for="show_fuel_rate"
                                class="form-label"
                            >
                                อัตรารถเปล่าหลังปรับตามอายุ
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    id="show_fuel_rate"
                                    class="form-control bg-light"
                                    step="0.01"
                                    readonly
                                >

                                <span class="input-group-text">
                                    กม./ลิตร
                                </span>
                            </div>

                            <div
                                id="fuel_rate_age_help"
                                class="form-text"
                            >
                                คำนวณจากอัตราตอนรถใหม่และปีที่ซื้อ
                            </div>
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
                                required
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

                    <hr>

                    <div class="mb-3">
                        <label
                            for="start_point"
                            class="form-label fw-semibold"
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
                            placeholder="ค้นหาจุดเริ่มต้น"
                            value="{{ old(
                                'start_point',
                                $fuel_record->start_point ?? ''
                            ) }}"
                            autocomplete="off"
                            required
                        >

                        <small class="text-muted">
                            พิมพ์ชื่อสถานที่ หรือคลิกเลือกตำแหน่งบนแผนที่
                        </small>

                        @error('start_point')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div
                        id="segments_container"
                        class="d-flex flex-column gap-3"
                    ></div>

                    <div class="d-flex flex-wrap gap-2 my-3">
                        <button
                            type="button"
                            id="add_segment_button"
                            class="btn btn-outline-primary"
                        >
                            + เพิ่มสถานที่ถัดไป
                        </button>

                        <button
                            type="button"
                            id="calculate_all_routes_button"
                            class="btn btn-primary"
                        >
                            คำนวณทุกช่วง
                        </button>

                        <button
                            type="button"
                            id="clear_routes_button"
                            class="btn btn-outline-secondary"
                        >
                            ล้างเส้นทาง
                        </button>
                    </div>

                    <input
                        type="hidden"
                        id="legacy_destination"
                        name="destination"
                        value="{{ old(
                            'destination',
                            $fuel_record->destination ?? ''
                        ) }}"
                    >

                    <input
                        type="hidden"
                        id="legacy_load_weight"
                        name="load_weight"
                        value="{{ old(
                            'load_weight',
                            $fuel_record->current_weight ?? 0
                        ) }}"
                    >

                    <div class="fuel-summary mb-3">
                        <div class="fw-semibold mb-2">
                            สรุปการเดินทางทั้งหมด
                        </div>

                        <div class="fuel-summary-row">
                            <span>
                                จำนวนช่วงเดินทาง
                            </span>

                            <strong id="segment_count_display">
                                0 ช่วง
                            </strong>
                        </div>

                        <div class="fuel-summary-row">
                            <span>
                                ระยะทางรวม
                            </span>

                            <strong id="total_distance_display">
                                0.00 กม.
                            </strong>
                        </div>

                        <div class="fuel-summary-row">
                            <span>
                                น้ำมันรวม
                            </span>

                            <strong id="total_fuel_display">
                                0.00 ลิตร
                            </strong>
                        </div>

                        <hr class="my-2">

                        <div class="fuel-summary-row">
                            <span>
                                ค่าน้ำมันรวม
                            </span>

                            <strong id="total_cost_display">
                                0.00 บาท
                            </strong>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label
                                for="distance"
                                class="form-label"
                            >
                                ระยะทางรวม
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

                        <div class="col-md-6 mb-3">
                            <label
                                for="cost_fuel_total"
                                class="form-label"
                            >
                                ค่าน้ำมันรวม
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
                                    required
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
                    </div>

                    <input
                        type="hidden"
                        id="total_fuel_liters"
                        name="total_fuel_liters"
                        value="{{ old(
                            'total_fuel_liters',
                            $fuel_record->total_fuel_liters ?? ''
                        ) }}"
                    >

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
                        คลิกช่องจุดเริ่มต้นหรือปลายทางที่ต้องการก่อน
                        แล้วคลิกตำแหน่งบนแผนที่
                    </div>

                    <div
                        id="map_legend"
                        class="map-legend mt-3"
                    ></div>

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

    <template id="segment_template">
        <div class="segment-card">
            <div class="segment-header">
                <div class="segment-title">
                    <span class="segment-color-dot"></span>

                    <span class="segment-title-text">
                        ช่วงที่ 1
                    </span>
                </div>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger remove-segment-button"
                >
                    ลบ
                </button>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    ต้นทางของช่วงนี้
                </label>

                <div class="segment-origin-box">
                    กรุณาเลือกจุดเริ่มต้น
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    สถานที่ถัดไป

                    <span class="text-danger">
                        *
                    </span>
                </label>

                <input
                    type="text"
                    class="form-control segment-destination"
                    placeholder="ค้นหาปลายทาง"
                    autocomplete="off"
                    required
                >

                <small class="text-muted">
                    เลือกจากรายการค้นหา หรือคลิกเลือกบนแผนที่
                </small>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        น้ำหนักบรรทุกช่วงนี้
                    </label>

                    <div class="input-group">
                        <input
                            type="number"
                            class="form-control segment-load-weight"
                            min="0"
                            step="0.01"
                            value="0"
                            required
                        >

                        <span class="input-group-text">
                            กก.
                        </span>
                    </div>

                    <small class="text-muted">
                        รถเปล่ากรอก 0
                    </small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        ระยะทาง
                    </label>

                    <div class="input-group">
                        <input
                            type="number"
                            class="form-control bg-light segment-distance"
                            min="0"
                            step="0.01"
                            readonly
                            required
                        >

                        <span class="input-group-text">
                            กม.
                        </span>
                    </div>
                </div>
            </div>

            <div class="segment-summary">
                <div class="segment-summary-row">
                    <span>
                        สัดส่วนบรรทุก
                    </span>

                    <strong class="segment-load-percentage-display">
                        - %
                    </strong>
                </div>

                <div class="segment-summary-row">
                    <span>
                        อัตราสิ้นเปลือง
                    </span>

                    <strong class="segment-fuel-rate-display">
                        - กม./ลิตร
                    </strong>
                </div>

                <div class="segment-summary-row">
                    <span>
                        น้ำมันที่ใช้
                    </span>

                    <strong class="segment-fuel-liters-display">
                        - ลิตร
                    </strong>
                </div>

                <div class="segment-summary-row">
                    <span>
                        ค่าน้ำมัน
                    </span>

                    <strong class="segment-fuel-cost-display">
                        - บาท
                    </strong>
                </div>
            </div>

            <input
                type="hidden"
                class="segment-start-point"
            >

            <input
                type="hidden"
                class="segment-fuel-rate"
            >

            <input
                type="hidden"
                class="segment-fuel-liters"
            >

            <input
                type="hidden"
                class="segment-fuel-cost"
            >

            <div class="route-options-box d-none">
                <div class="fw-semibold mb-2">
                    ทางเลือก
                </div>

                <div class="segment-route-options d-flex flex-column gap-2"></div>
            </div>
        </div>
    </template>

    <script>
        (() => {
            const FULL_LOAD_EFFICIENCY_LOSS = 0.20;

            // ประสิทธิภาพ กม./ลิตร ลดลงตามอายุปีละ 0.75%
            const ANNUAL_AGE_EFFICIENCY_LOSS = 0.0075;

            // จำกัดผลจากอายุไม่ให้ลดลงเกิน 10%
            const MAXIMUM_AGE_EFFICIENCY_LOSS = 0.10;

            const ROUTE_COLORS = [
                '#0d6efd',
                '#dc3545',
                '#198754',
                '#fd7e14',
                '#6f42c1',
                '#0dcaf0',
                '#d63384',
                '#20c997',
            ];

            const defaultCenter = {
                lat: 16.4322,
                lng: 102.8236,
            };

            const initialSegments = @json($initialSegments);

            const form = document.getElementById(
                'fuel_record_form'
            );

            const dateRecordInput = document.getElementById(
                'date_record'
            );

            const startInput = document.getElementById(
                'start_point'
            );

            const truckSelect = document.getElementById(
                'truck_select'
            );

            const maximumLoadInput = document.getElementById(
                'max_load_weight'
            );

            const emptyFuelRateInput = document.getElementById(
                'show_fuel_rate'
            );

            const fuelRateAgeHelp = document.getElementById(
                'fuel_rate_age_help'
            );

            const fuelPriceInput = document.getElementById(
                'cost_fuel'
            );

            const segmentsContainer = document.getElementById(
                'segments_container'
            );

            const segmentTemplate = document.getElementById(
                'segment_template'
            );

            const addSegmentButton = document.getElementById(
                'add_segment_button'
            );

            const calculateAllRoutesButton = document.getElementById(
                'calculate_all_routes_button'
            );

            const clearRoutesButton = document.getElementById(
                'clear_routes_button'
            );

            const distanceInput = document.getElementById(
                'distance'
            );

            const fuelTotalInput = document.getElementById(
                'cost_fuel_total'
            );

            const totalFuelLitersInput = document.getElementById(
                'total_fuel_liters'
            );

            const legacyDestinationInput = document.getElementById(
                'legacy_destination'
            );

            const legacyLoadWeightInput = document.getElementById(
                'legacy_load_weight'
            );

            const segmentCountDisplay = document.getElementById(
                'segment_count_display'
            );

            const totalDistanceDisplay = document.getElementById(
                'total_distance_display'
            );

            const totalFuelDisplay = document.getElementById(
                'total_fuel_display'
            );

            const totalCostDisplay = document.getElementById(
                'total_cost_display'
            );

            const mapLegend = document.getElementById(
                'map_legend'
            );

            const statusElement = document.getElementById(
                'map_status'
            );

            const errorElement = document.getElementById(
                'map_error'
            );

            let map = null;

            let directionsService = null;

            let placesService = null;

            let startLocation = null;

            let startAutocomplete = null;

            let activeField = {
                type: 'start',
                segmentId: null,
            };

            let segmentCounter = 0;

            let routeCalculationVersion = 0;

            let segments = [];

            let mapMarkers = [];

            function showStatus(message) {
                statusElement.textContent = message;

                statusElement.classList.remove(
                    'd-none'
                );
            }

            function clearStatus() {
                statusElement.textContent = '';

                statusElement.classList.add(
                    'd-none'
                );
            }

            function showError(message) {
                clearStatus();

                errorElement.textContent = message;

                errorElement.classList.remove(
                    'd-none'
                );
            }

            function clearError() {
                errorElement.textContent = '';

                errorElement.classList.add(
                    'd-none'
                );
            }

            function getRouteColor(index) {
                return ROUTE_COLORS[
                    index % ROUTE_COLORS.length
                ];
            }

            function getCalculationYear() {
                const dateValue = dateRecordInput.value;

                if (dateValue) {
                    const selectedYear = Number.parseInt(
                        dateValue.substring(0, 4),
                        10
                    );

                    if (Number.isInteger(selectedYear)) {
                        return selectedYear;
                    }
                }

                return new Date().getFullYear();
            }

            function calculateFuelRateByAge(
                baseFuelRate,
                purchaseYear
            ) {
                const calculationYear = getCalculationYear();

                const hasPurchaseYear =
                    Number.isInteger(purchaseYear) &&
                    purchaseYear > 0;

                // หากไม่มีปีที่ซื้อ จะใช้ค่าเดิมโดยไม่หักตามอายุ
                const truckAge = hasPurchaseYear
                    ? Math.max(calculationYear - purchaseYear, 0)
                    : 0;

                const ageLossRatio = Math.min(
                    truckAge * ANNUAL_AGE_EFFICIENCY_LOSS,
                    MAXIMUM_AGE_EFFICIENCY_LOSS
                );

                const adjustedFuelRate =
                    baseFuelRate * (1 - ageLossRatio);

                return {
                    calculationYear,
                    purchaseYear: hasPurchaseYear
                        ? purchaseYear
                        : null,
                    truckAge,
                    ageLossRatio,
                    baseFuelRate,
                    adjustedFuelRate,
                };
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

                const baseFuelRate = Number.parseFloat(
                    selectedOption.dataset.fuelRate
                );

                const purchaseYear = Number.parseInt(
                    selectedOption.dataset.purchaseYear,
                    10
                );

                const ageInformation = calculateFuelRateByAge(
                    baseFuelRate,
                    purchaseYear
                );

                return {
                    // ค่า กม./ลิตรตอนรถใหม่จากตาราง trucks
                    baseFuelRate,

                    // ค่า กม./ลิตรหลังปรับลดตามอายุ
                    fuelRate: ageInformation.adjustedFuelRate,

                    purchaseYear: ageInformation.purchaseYear,

                    calculationYear: ageInformation.calculationYear,

                    truckAge: ageInformation.truckAge,

                    ageLossRatio: ageInformation.ageLossRatio,

                    maximumLoad: Number.parseFloat(
                        selectedOption.dataset.maxLoad
                    ),
                };
            }

            function updateTruckInformation() {
                const information =
                    getSelectedTruckInformation();

                if (!information) {
                    maximumLoadInput.value = '';

                    emptyFuelRateInput.value = '';

                    fuelRateAgeHelp.textContent =
                        'กรุณาเลือกรถบรรทุก';

                    segments.forEach(
                        function (segment) {
                            segment.weightInput.removeAttribute(
                                'max'
                            );
                        }
                    );

                    return;
                }

                maximumLoadInput.value =
                    Number.isFinite(
                        information.maximumLoad
                    ) &&
                    information.maximumLoad > 0
                        ? information.maximumLoad.toFixed(2)
                        : '';

                emptyFuelRateInput.value =
                    Number.isFinite(
                        information.fuelRate
                    ) &&
                    information.fuelRate > 0
                        ? information.fuelRate.toFixed(2)
                        : '';

                if (
                    Number.isFinite(information.baseFuelRate) &&
                    information.baseFuelRate > 0
                ) {
                    if (information.purchaseYear) {
                        fuelRateAgeHelp.textContent =
                            `ค่าตอนรถใหม่ ${information.baseFuelRate.toFixed(2)} กม./ลิตร | ` +
                            `ซื้อปี ${information.purchaseYear} | ` +
                            `อายุ ณ ปี ${information.calculationYear} = ${information.truckAge} ปี | ` +
                            `ลดตามอายุ ${(information.ageLossRatio * 100).toFixed(2)}%`;
                    } else {
                        fuelRateAgeHelp.textContent =
                            'รถคันนี้ไม่มีข้อมูลปีที่ซื้อ จึงยังไม่หักผลจากอายุรถ';
                    }
                } else {
                    fuelRateAgeHelp.textContent =
                        'รถคันนี้ไม่มีข้อมูลอัตราสิ้นเปลือง';
                }

                segments.forEach(
                    function (segment) {
                        if (
                            Number.isFinite(
                                information.maximumLoad
                            ) &&
                            information.maximumLoad > 0
                        ) {
                            segment.weightInput.max =
                                String(
                                    information.maximumLoad
                                );
                        } else {
                            segment.weightInput.removeAttribute(
                                'max'
                            );
                        }
                    }
                );
            }

            function getSegmentIndex(segmentId) {
                return segments.findIndex(
                    function (segment) {
                        return segment.id === segmentId;
                    }
                );
            }

            function getSegment(segmentId) {
                return segments.find(
                    function (segment) {
                        return segment.id === segmentId;
                    }
                ) || null;
            }

            function getSegmentOriginText(index) {
                if (index === 0) {
                    return startInput.value.trim();
                }

                return segments[
                    index - 1
                ]?.destinationInput.value.trim() || '';
            }

            function getSegmentOriginLocation(index) {
                if (index === 0) {
                    return startLocation;
                }

                return segments[
                    index - 1
                ]?.destinationLocation || null;
            }

            function setActiveField(
                type,

                segmentId = null
            ) {
                activeField = {
                    type,
                    segmentId,
                };

                startInput.classList.toggle(
                    'location-input-active',

                    type === 'start'
                );

                segments.forEach(
                    function (segment) {
                        const isActive =
                            type === 'destination' &&
                            segment.id === segmentId;

                        segment.destinationInput.classList.toggle(
                            'location-input-active',

                            isActive
                        );

                        segment.card.classList.toggle(
                            'active',

                            isActive
                        );
                    }
                );
            }

            function createSegment(data = {}) {
                const fragment =
                    segmentTemplate.content.cloneNode(
                        true
                    );

                const card =
                    fragment.querySelector(
                        '.segment-card'
                    );

                const titleElement =
                    fragment.querySelector(
                        '.segment-title-text'
                    );

                const colorDot =
                    fragment.querySelector(
                        '.segment-color-dot'
                    );

                const originBox =
                    fragment.querySelector(
                        '.segment-origin-box'
                    );

                const destinationInput =
                    fragment.querySelector(
                        '.segment-destination'
                    );

                const weightInput =
                    fragment.querySelector(
                        '.segment-load-weight'
                    );

                const distanceInputElement =
                    fragment.querySelector(
                        '.segment-distance'
                    );

                const startPointInput =
                    fragment.querySelector(
                        '.segment-start-point'
                    );

                const fuelRateInput =
                    fragment.querySelector(
                        '.segment-fuel-rate'
                    );

                const fuelLitersInput =
                    fragment.querySelector(
                        '.segment-fuel-liters'
                    );

                const fuelCostInput =
                    fragment.querySelector(
                        '.segment-fuel-cost'
                    );

                const loadPercentageDisplay =
                    fragment.querySelector(
                        '.segment-load-percentage-display'
                    );

                const fuelRateDisplay =
                    fragment.querySelector(
                        '.segment-fuel-rate-display'
                    );

                const fuelLitersDisplay =
                    fragment.querySelector(
                        '.segment-fuel-liters-display'
                    );

                const fuelCostDisplay =
                    fragment.querySelector(
                        '.segment-fuel-cost-display'
                    );

                const removeButton =
                    fragment.querySelector(
                        '.remove-segment-button'
                    );

                const routeOptionsBox =
                    fragment.querySelector(
                        '.route-options-box'
                    );

                const routeOptionsContainer =
                    fragment.querySelector(
                        '.segment-route-options'
                    );

                segmentCounter += 1;

                const segmentId =
                    segmentCounter;

                destinationInput.value =
                    data.destination || '';

                weightInput.value =
                    String(
                        data.load_weight ?? 0
                    );

                distanceInputElement.value =
                    data.distance || '';

                fuelRateInput.value =
                    data.fuel_rate || '';

                fuelLitersInput.value =
                    data.fuel_liters || '';

                fuelCostInput.value =
                    data.fuel_cost || '';

                const segment = {
                    id: segmentId,

                    card,

                    titleElement,

                    colorDot,

                    originBox,

                    destinationInput,

                    weightInput,

                    distanceInput:
                        distanceInputElement,

                    startPointInput,

                    fuelRateInput,

                    fuelLitersInput,

                    fuelCostInput,

                    loadPercentageDisplay,

                    fuelRateDisplay,

                    fuelLitersDisplay,

                    fuelCostDisplay,

                    removeButton,

                    routeOptionsBox,

                    routeOptionsContainer,

                    destinationLocation:
                        null,

                    autocomplete:
                        null,

                    routes: [],

                    selectedRouteIndex:
                        0,

                    polylines: [],
                };

                segments.push(
                    segment
                );

                segmentsContainer.appendChild(
                    fragment
                );

                destinationInput.addEventListener(
                    'focus',

                    function () {
                        setActiveField(
                            'destination',

                            segment.id
                        );
                    }
                );

                destinationInput.addEventListener(
                    'input',

                    function () {
                        handleDestinationInput(
                            segment.id
                        );
                    }
                );

                weightInput.addEventListener(
                    'input',

                    function () {
                        handleWeightInput(
                            segment.id
                        );
                    }
                );

                removeButton.addEventListener(
                    'click',

                    function () {
                        removeSegment(
                            segment.id
                        );
                    }
                );

                if (
                    map &&
                    google.maps.places
                ) {
                    initializeSegmentAutocomplete(
                        segment
                    );
                }

                refreshSegmentIndexes();

                updateTruckInformation();

                updateSegmentPreview(
                    segment
                );

                updateTotals();

                return segment;
            }

            function refreshSegmentIndexes() {
                segments.forEach(
                    function (
                        segment,

                        index
                    ) {
                        const segmentNumber =
                            index + 1;

                        const routeColor =
                            getRouteColor(
                                index
                            );

                        segment.titleElement.textContent =
                            `ช่วงที่ ${segmentNumber}`;

                        segment.colorDot.style.backgroundColor =
                            routeColor;

                        segment.destinationInput.name =
                            `segments[${index}][destination]`;

                        segment.weightInput.name =
                            `segments[${index}][load_weight]`;

                        segment.distanceInput.name =
                            `segments[${index}][distance]`;

                        segment.startPointInput.name =
                            `segments[${index}][start_point]`;

                        segment.fuelRateInput.name =
                            `segments[${index}][fuel_rate]`;

                        segment.fuelLitersInput.name =
                            `segments[${index}][fuel_liters]`;

                        segment.fuelCostInput.name =
                            `segments[${index}][fuel_cost]`;

                        segment.removeButton.disabled =
                            segments.length === 1;

                        segment.polylines.forEach(
                            function (
                                polyline,

                                routeIndex
                            ) {
                                polyline.setOptions(
                                    getPolylineStyle(
                                        index,

                                        routeIndex ===
                                            segment.selectedRouteIndex
                                    )
                                );
                            }
                        );
                    }
                );

                refreshSegmentOrigins();

                renderMapLegend();

                updateLegacyFields();
            }

            function refreshSegmentOrigins() {
                segments.forEach(
                    function (
                        segment,

                        index
                    ) {
                        const originText =
                            getSegmentOriginText(
                                index
                            );

                        segment.originBox.textContent =
                            originText ||
                            (
                                index === 0
                                    ? 'กรุณาเลือกจุดเริ่มต้น'
                                    : 'กรุณาเลือกปลายทางของช่วงก่อนหน้า'
                            );

                        segment.startPointInput.value =
                            originText;
                    }
                );

                updateLegacyFields();
            }

            function updateLegacyFields() {
                if (!segments.length) {
                    legacyDestinationInput.value =
                        '';

                    legacyLoadWeightInput.value =
                        '0';

                    return;
                }

                const lastSegment =
                    segments[
                        segments.length - 1
                    ];

                legacyDestinationInput.value =
                    lastSegment.destinationInput.value.trim();

                const weights =
                    segments.map(
                        function (segment) {
                            const weight =
                                Number.parseFloat(
                                    segment.weightInput.value || '0'
                                );

                            return Number.isFinite(
                                weight
                            )
                                ? weight
                                : 0;
                        }
                    );

                legacyLoadWeightInput.value =
                    String(
                        Math.max(
                            0,

                            ...weights
                        )
                    );
            }

            function clearSegmentSummary(segment) {
                segment.loadPercentageDisplay.textContent =
                    '- %';

                segment.fuelRateDisplay.textContent =
                    '- กม./ลิตร';

                segment.fuelLitersDisplay.textContent =
                    '- ลิตร';

                segment.fuelCostDisplay.textContent =
                    '- บาท';

                segment.fuelRateInput.value =
                    '';

                segment.fuelLitersInput.value =
                    '';

                segment.fuelCostInput.value =
                    '';
            }

            function clearSegmentRoute(segment) {
                segment.polylines.forEach(
                    function (polyline) {
                        polyline.setMap(
                            null
                        );
                    }
                );

                segment.polylines =
                    [];

                segment.routes =
                    [];

                segment.selectedRouteIndex =
                    0;

                segment.distanceInput.value =
                    '';

                segment.routeOptionsContainer.innerHTML =
                    '';

                segment.routeOptionsBox.classList.add(
                    'd-none'
                );

                clearSegmentSummary(
                    segment
                );
            }

            function invalidateSegmentAndFollowing(
                startIndex
            ) {
                routeCalculationVersion += 1;

                segments.forEach(
                    function (
                        segment,

                        index
                    ) {
                        if (
                            index >= startIndex
                        ) {
                            clearSegmentRoute(
                                segment
                            );
                        }
                    }
                );

                refreshSegmentOrigins();

                refreshMapMarkers();

                updateTotals();
            }

            function removeSegment(segmentId) {
                if (
                    segments.length <= 1
                ) {
                    return;
                }

                const index =
                    getSegmentIndex(
                        segmentId
                    );

                if (
                    index < 0
                ) {
                    return;
                }

                const segment =
                    segments[
                        index
                    ];

                clearSegmentRoute(
                    segment
                );

                if (
                    segment.autocomplete
                ) {
                    google.maps.event.clearInstanceListeners(
                        segment.autocomplete
                    );
                }

                segment.card.remove();

                segments.splice(
                    index,

                    1
                );

                refreshSegmentIndexes();

                invalidateSegmentAndFollowing(
                    index
                );

                clearError();

                clearStatus();

                if (
                    segments.length
                ) {
                    const activeSegment =
                        segments[
                            Math.min(
                                index,

                                segments.length - 1
                            )
                        ];

                    setActiveField(
                        'destination',

                        activeSegment.id
                    );
                } else {
                    setActiveField(
                        'start'
                    );
                }
            }

            function getSegmentLoadInformation(
                segment,

                segmentIndex
            ) {
                const truckInformation =
                    getSelectedTruckInformation();

                if (
                    !truckInformation
                ) {
                    throw new Error(
                        'กรุณาเลือกรถบรรทุก'
                    );
                }

                const maximumLoad =
                    truckInformation.maximumLoad;

                const emptyFuelRate =
                    truckInformation.fuelRate;

                if (
                    !Number.isFinite(
                        maximumLoad
                    ) ||
                    maximumLoad <= 0
                ) {
                    throw new Error(
                        'รถคันนี้ไม่มีข้อมูลน้ำหนักบรรทุกสูงสุด'
                    );
                }

                if (
                    !Number.isFinite(
                        emptyFuelRate
                    ) ||
                    emptyFuelRate <= 0
                ) {
                    throw new Error(
                        'รถคันนี้ไม่มีข้อมูลอัตราสิ้นเปลือง'
                    );
                }

                const loadWeight =
                    Number.parseFloat(
                        segment.weightInput.value || '0'
                    );

                if (
                    !Number.isFinite(
                        loadWeight
                    ) ||
                    loadWeight < 0
                ) {
                    throw new Error(
                        `กรุณาตรวจสอบน้ำหนักบรรทุกช่วงที่ ${segmentIndex + 1}`
                    );
                }

                if (
                    loadWeight > maximumLoad
                ) {
                    throw new Error(
                        `น้ำหนักบรรทุกช่วงที่ ${segmentIndex + 1} ต้องไม่เกิน ${maximumLoad.toFixed(2)} กก.`
                    );
                }

                const loadRatio =
                    loadWeight /
                    maximumLoad;

                const fuelRate =
                    emptyFuelRate *
                    (
                        1 -
                        (
                            loadRatio *
                            FULL_LOAD_EFFICIENCY_LOSS
                        )
                    );

                if (
                    !Number.isFinite(
                        fuelRate
                    ) ||
                    fuelRate <= 0
                ) {
                    throw new Error(
                        `ไม่สามารถคำนวณอัตราสิ้นเปลืองช่วงที่ ${segmentIndex + 1} ได้`
                    );
                }

                return {
                    maximumLoad,

                    emptyFuelRate,

                    loadWeight,

                    loadRatio,

                    fuelRate,
                };
            }

            function getSegmentFuelInformation(
                segment,

                distance
            ) {
                const segmentIndex =
                    getSegmentIndex(
                        segment.id
                    );

                const loadInformation =
                    getSegmentLoadInformation(
                        segment,

                        segmentIndex
                    );

                if (
                    !Number.isFinite(
                        distance
                    ) ||
                    distance <= 0
                ) {
                    throw new Error(
                        `กรุณาคำนวณเส้นทางช่วงที่ ${segmentIndex + 1}`
                    );
                }

                const fuelPrice =
                    Number.parseFloat(
                        fuelPriceInput.value
                    );

                if (
                    !Number.isFinite(
                        fuelPrice
                    ) ||
                    fuelPrice <= 0
                ) {
                    throw new Error(
                        'กรุณาตรวจสอบราคาน้ำมัน'
                    );
                }

                const fuelLiters =
                    distance /
                    loadInformation.fuelRate;

                const fuelCost =
                    fuelLiters *
                    fuelPrice;

                return {
                    ...loadInformation,

                    distance,

                    fuelPrice,

                    fuelLiters,

                    fuelCost,
                };
            }

            function updateSegmentPreview(segment) {
                const segmentIndex =
                    getSegmentIndex(
                        segment.id
                    );

                try {
                    const loadInformation =
                        getSegmentLoadInformation(
                            segment,

                            segmentIndex
                        );

                    segment.loadPercentageDisplay.textContent =
                        `${(loadInformation.loadRatio * 100).toFixed(2)} %`;

                    segment.fuelRateDisplay.textContent =
                        `${loadInformation.fuelRate.toFixed(2)} กม./ลิตร`;

                    segment.fuelRateInput.value =
                        loadInformation.fuelRate.toFixed(2);

                    return true;
                } catch (error) {
                    clearSegmentSummary(
                        segment
                    );

                    return false;
                }
            }

            function calculateSegmentFuel(
                segment,

                options = {}
            ) {
                const {
                    showErrors = false,
                } = options;

                updateSegmentPreview(
                    segment
                );

                const distance =
                    Number.parseFloat(
                        segment.distanceInput.value
                    );

                if (
                    !Number.isFinite(
                        distance
                    ) ||
                    distance <= 0
                ) {
                    segment.fuelLitersInput.value =
                        '';

                    segment.fuelCostInput.value =
                        '';

                    segment.fuelLitersDisplay.textContent =
                        '- ลิตร';

                    segment.fuelCostDisplay.textContent =
                        '- บาท';

                    return null;
                }

                try {
                    const information =
                        getSegmentFuelInformation(
                            segment,

                            distance
                        );

                    segment.loadPercentageDisplay.textContent =
                        `${(information.loadRatio * 100).toFixed(2)} %`;

                    segment.fuelRateDisplay.textContent =
                        `${information.fuelRate.toFixed(2)} กม./ลิตร`;

                    segment.fuelLitersDisplay.textContent =
                        `${information.fuelLiters.toFixed(2)} ลิตร`;

                    segment.fuelCostDisplay.textContent =
                        `${information.fuelCost.toFixed(2)} บาท`;

                    segment.fuelRateInput.value =
                        information.fuelRate.toFixed(2);

                    segment.fuelLitersInput.value =
                        information.fuelLiters.toFixed(2);

                    segment.fuelCostInput.value =
                        information.fuelCost.toFixed(2);

                    return information;
                } catch (error) {
                    clearSegmentSummary(
                        segment
                    );

                    if (
                        showErrors
                    ) {
                        showError(
                            error.message
                        );
                    }

                    return null;
                }
            }

            function updateTotals() {
                let totalDistance = 0;

                let totalFuelLiters = 0;

                let totalFuelCost = 0;

                let calculatedSegments = 0;

                segments.forEach(
                    function (segment) {
                        const distance =
                            Number.parseFloat(
                                segment.distanceInput.value
                            );

                        const fuelLiters =
                            Number.parseFloat(
                                segment.fuelLitersInput.value
                            );

                        const fuelCost =
                            Number.parseFloat(
                                segment.fuelCostInput.value
                            );

                        if (
                            Number.isFinite(
                                distance
                            ) &&
                            distance > 0
                        ) {
                            totalDistance +=
                                distance;
                        }

                        if (
                            Number.isFinite(
                            fuelLiters
                            ) &&
                            fuelLiters >= 0
                        ) {
                            totalFuelLiters +=
                                fuelLiters;
                        }

                        if (
                            Number.isFinite(
                                fuelCost
                            ) &&
                            fuelCost >= 0
                        ) {
                            totalFuelCost +=
                                fuelCost;
                        }

                        if (
                            Number.isFinite(
                                distance
                            ) &&
                            distance > 0 &&
                            Number.isFinite(
                                fuelLiters
                            ) &&
                            Number.isFinite(
                                fuelCost
                            )
                        ) {
                            calculatedSegments += 1;
                        }
                    }
                );

                segmentCountDisplay.textContent =
                    `${calculatedSegments} / ${segments.length} ช่วง`;

                totalDistanceDisplay.textContent =
                    `${totalDistance.toFixed(2)} กม.`;

                totalFuelDisplay.textContent =
                    `${totalFuelLiters.toFixed(2)} ลิตร`;

                totalCostDisplay.textContent =
                    `${totalFuelCost.toFixed(2)} บาท`;

                distanceInput.value =
                    totalDistance > 0
                        ? totalDistance.toFixed(2)
                        : '';

                totalFuelLitersInput.value =
                    calculatedSegments > 0
                        ? totalFuelLiters.toFixed(2)
                        : '';

                fuelTotalInput.value =
                    calculatedSegments > 0
                        ? totalFuelCost.toFixed(2)
                        : '';

                updateLegacyFields();
            }

            function updateAllFuelCalculations(
                options = {}
            ) {
                segments.forEach(
                    function (segment) {
                        calculateSegmentFuel(
                            segment,

                            options
                        );

                        renderRouteOptions(
                            segment
                        );
                    }
                );

                updateTotals();
            }

            function handleWeightInput(segmentId) {
                const segment =
                    getSegment(
                        segmentId
                    );

                if (
                    !segment
                ) {
                    return;
                }

                const index =
                    getSegmentIndex(
                        segment.id
                    );

                try {
                    getSegmentLoadInformation(
                        segment,

                        index
                    );

                    clearError();

                    calculateSegmentFuel(
                        segment
                    );

                    renderRouteOptions(
                        segment
                    );
                } catch (error) {
                    clearSegmentSummary(
                        segment
                    );

                    showError(
                        error.message
                    );
                }

                updateTotals();
            }

            function handleDestinationInput(
                segmentId
            ) {
                const segment =
                    getSegment(
                        segmentId
                    );

                if (
                    !segment
                ) {
                    return;
                }

                const index =
                    getSegmentIndex(
                        segmentId
                    );

                segment.destinationLocation =
                    null;

                invalidateSegmentAndFollowing(
                    index
                );

                clearError();

                clearStatus();
            }

            function formatCoordinates(position) {
                return [
                    position.lat().toFixed(6),

                    position.lng().toFixed(6),
                ].join(
                    ','
                );
            }

            function buildPlaceLabel(place) {
                const name =
                    place.name || '';

                const address =
                    place.formatted_address || '';

                if (
                    !name
                ) {
                    return address;
                }

                if (
                    !address ||
                    address.includes(
                        name
                    )
                ) {
                    return address || name;
                }

                return `${name}, ${address}`;
            }

            function removeMapMarkers() {
                mapMarkers.forEach(
                    function (marker) {
                        marker.setMap(
                            null
                        );
                    }
                );

                mapMarkers =
                    [];
            }

            function refreshMapMarkers() {
                if (
                    !map
                ) {
                    return;
                }

                removeMapMarkers();

                const firstRoute =
                    segments[0]?.routes[
                        segments[0]?.selectedRouteIndex || 0
                    ] || null;

                const firstLeg =
                    firstRoute?.legs?.[0] || null;

                const startPosition =
                    startLocation ||
                    firstLeg?.start_location ||
                    null;

                if (
                    startPosition
                ) {
                    mapMarkers.push(
                        new google.maps.Marker({
                            map,

                            position:
                                startPosition,

                            label:
                                '1',

                            title:
                                startInput.value.trim() ||
                                'จุดเริ่มต้น',
                        })
                    );
                }

                segments.forEach(
                    function (
                        segment,

                        index
                    ) {
                        const selectedRoute =
                            segment.routes[
                                segment.selectedRouteIndex
                            ];

                        const leg =
                            selectedRoute?.legs?.[0] ||
                            null;

                        const destinationPosition =
                            segment.destinationLocation ||
                            leg?.end_location ||
                            null;

                        if (
                            !destinationPosition
                        ) {
                            return;
                        }

                        mapMarkers.push(
                            new google.maps.Marker({
                                map,

                                position:
                                    destinationPosition,

                                label:
                                    String(
                                        index + 2
                                    ),

                                title:
                                    segment.destinationInput.value.trim(),
                            })
                        );
                    }
                );
            }

            function getRouteLeg(route) {
                return route?.legs?.[0] || null;
            }

            function getRouteDistance(route) {
                const leg =
                    getRouteLeg(
                        route
                    );

                if (
                    !leg?.distance?.value
                ) {
                    return 0;
                }

                return leg.distance.value / 1000;
            }

            function getRouteDuration(route) {
                const leg =
                    getRouteLeg(
                        route
                    );

                if (
                    !leg?.duration?.value
                ) {
                    return 0;
                }

                return Math.round(
                    leg.duration.value / 60
                );
            }

            function formatDuration(
                totalMinutes
            ) {
                if (
                    !Number.isFinite(
                        totalMinutes
                    ) ||
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

                if (
                    hours <= 0
                ) {
                    return `${minutes} นาที`;
                }

                if (
                    minutes === 0
                ) {
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

                if (
                    routeSummary
                ) {
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

                const path =
                    [];

                (
                    route?.legs || []
                ).forEach(
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

            function getPolylineStyle(
                segmentIndex,

                isSelected
            ) {
                return {
                    strokeColor:
                        isSelected
                            ? getRouteColor(
                                segmentIndex
                            )
                            : '#9aa4af',

                    strokeOpacity:
                        isSelected
                            ? 0.90
                            : 0.45,

                    strokeWeight:
                        isSelected
                            ? 7
                            : 4,

                    zIndex:
                        isSelected
                            ? 100 + segmentIndex
                            : 10,

                    clickable:
                        true,
                };
            }

            function drawSegmentRoutes(segment) {
                const segmentIndex =
                    getSegmentIndex(
                        segment.id
                    );

                segment.polylines.forEach(
                    function (polyline) {
                        polyline.setMap(
                            null
                        );
                    }
                );

                segment.polylines =
                    [];

                segment.routes.forEach(
                    function (
                        route,

                        routeIndex
                    ) {
                        const path =
                            getRoutePath(
                                route
                            );

                        if (
                            !path.length
                        ) {
                            return;
                        }

                        const polyline =
                            new google.maps.Polyline({
                                map,

                                path,

                                ...getPolylineStyle(
                                    segmentIndex,

                                    routeIndex ===
                                        segment.selectedRouteIndex
                                ),
                            });

                        polyline.addListener(
                            'click',

                            function () {
                                selectSegmentRoute(
                                    segment.id,

                                    routeIndex
                                );
                            }
                        );

                        segment.polylines.push(
                            polyline
                        );
                    }
                );
            }

            function refreshSegmentRouteStyles(
                segment
            ) {
                const segmentIndex =
                    getSegmentIndex(
                        segment.id
                    );

                segment.polylines.forEach(
                    function (
                        polyline,

                        routeIndex
                    ) {
                        polyline.setOptions(
                            getPolylineStyle(
                                segmentIndex,

                                routeIndex ===
                                    segment.selectedRouteIndex
                            )
                        );
                    }
                );

                segment.routeOptionsContainer
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
                                    segment.selectedRouteIndex
                            );
                        }
                    );
            }

            function calculateEstimatedSegmentCost(
                segment,

                distance
            ) {
                try {
                    return getSegmentFuelInformation(
                        segment,

                        distance
                    ).fuelCost;
                } catch (error) {
                    return null;
                }
            }

            function renderRouteOptions(
                segment
            ) {
                segment.routeOptionsContainer.innerHTML =
                    '';

                if (
                    segment.routes.length <= 1
                ) {
                    segment.routeOptionsBox.classList.add(
                        'd-none'
                    );

                    return;
                }

                segment.routes.forEach(
                    function (
                        route,

                        routeIndex
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
                            calculateEstimatedSegmentCost(
                                segment,

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
                            String(
                                routeIndex
                            );

                        button.classList.toggle(
                            'active',

                            routeIndex ===
                                segment.selectedRouteIndex
                        );

                        const title =
                            document.createElement(
                                'div'
                            );

                        title.className =
                            'route-option-title';

                        title.textContent =
                            `${getRouteSummary(route, routeIndex)} — ${formatDuration(routeDuration)}`;

                        const details =
                            document.createElement(
                                'div'
                            );

                        details.className =
                            'route-option-details';

                        const detailParts = [
                            `${routeDistance.toFixed(2)} กม.`,
                        ];

                        if (
                            Number.isFinite(
                                estimatedCost
                            )
                        ) {
                            detailParts.push(
                                `ประมาณ ${estimatedCost.toFixed(2)} บาท`
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
                                selectSegmentRoute(
                                    segment.id,

                                    routeIndex
                                );
                            }
                        );

                        segment.routeOptionsContainer.appendChild(
                            button
                        );
                    }
                );

                segment.routeOptionsBox.classList.remove(
                    'd-none'
                );
            }

            function selectSegmentRoute(
                segmentId,

                routeIndex
            ) {
                const segment =
                    getSegment(
                        segmentId
                    );

                if (
                    !segment
                ) {
                    return;
                }

                const route =
                    segment.routes[
                        routeIndex
                    ];

                if (
                    !route
                ) {
                    return;
                }

                const routeDistance =
                    getRouteDistance(
                        route
                    );

                if (
                    routeDistance <= 0
                ) {
                    showError(
                        'ไม่พบข้อมูลระยะทางของเส้นทางนี้'
                    );

                    return;
                }

                segment.selectedRouteIndex =
                    routeIndex;

                segment.distanceInput.value =
                    routeDistance.toFixed(2);

                refreshSegmentRouteStyles(
                    segment
                );

                calculateSegmentFuel(
                    segment,

                    {
                        showErrors: true,
                    }
                );

                renderRouteOptions(
                    segment
                );

                refreshMapMarkers();

                updateTotals();
            }

            async function calculateSegmentRoute(
                segment,

                calculationVersion
            ) {
                if (
                    !map ||
                    !directionsService
                ) {
                    throw new Error(
                        'Google Maps ยังโหลดไม่เสร็จ กรุณารอสักครู่'
                    );
                }

                const index =
                    getSegmentIndex(
                        segment.id
                    );

                const originText =
                    getSegmentOriginText(
                        index
                    );

                const destinationText =
                    segment.destinationInput.value.trim();

                if (
                    !originText
                ) {
                    throw new Error(
                        `กรุณาระบุต้นทางของช่วงที่ ${index + 1}`
                    );
                }

                if (
                    !destinationText
                ) {
                    throw new Error(
                        `กรุณาระบุปลายทางของช่วงที่ ${index + 1}`
                    );
                }

                getSegmentLoadInformation(
                    segment,

                    index
                );

                const origin =
                    getSegmentOriginLocation(
                        index
                    ) ||
                    originText;

                const destination =
                    segment.destinationLocation ||
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
                    calculationVersion !==
                    routeCalculationVersion
                ) {
                    return false;
                }

                if (
                    !Array.isArray(
                        result.routes
                    ) ||
                    result.routes.length === 0
                ) {
                    throw new Error(
                        `ไม่พบเส้นทางสำหรับช่วงที่ ${index + 1}`
                    );
                }

                clearSegmentRoute(
                    segment
                );

                segment.routes =
                    result.routes;

                segment.selectedRouteIndex =
                    0;

                drawSegmentRoutes(
                    segment
                );

                renderRouteOptions(
                    segment
                );

                selectSegmentRoute(
                    segment.id,

                    0
                );

                return true;
            }

            function fitRoutesToMap() {
                if (
                    !map
                ) {
                    return;
                }

                const bounds =
                    new google.maps.LatLngBounds();

                let hasPoints =
                    false;

                segments.forEach(
                    function (segment) {
                        const route =
                            segment.routes[
                                segment.selectedRouteIndex
                            ];

                        if (
                            !route
                        ) {
                            return;
                        }

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

                if (
                    hasPoints
                ) {
                    map.fitBounds(
                        bounds,

                        60
                    );
                }
            }

            function normalizeDirectionsError(
                error
            ) {
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
                    return 'ไม่พบเส้นทางรถยนต์ระหว่างสถานที่ที่เลือก';
                }

                if (
                    errorText.includes(
                        'NOT_FOUND'
                    )
                ) {
                    return 'ไม่พบสถานที่ กรุณาเลือกสถานที่จากรายการที่ Google แนะนำ';
                }

                if (
                    errorText.includes(
                        'REQUEST_DENIED'
                    ) ||
                    errorText.includes(
                        'API_KEY'
                    )
                ) {
                    return 'Google ปฏิเสธคำขอ กรุณาตรวจสอบ Billing, Directions API, Places API และ API Key';
                }

                if (
                    errorText.includes(
                        'OVER_QUERY_LIMIT'
                    )
                ) {
                    return 'มีการเรียก Google Maps มากเกินไป กรุณารอสักครู่แล้วลองใหม่';
                }

                return error.message ||
                    'ไม่สามารถคำนวณเส้นทางได้';
            }

            async function calculateAllRoutes() {
                if (
                    !segments.length
                ) {
                    showError(
                        'กรุณาเพิ่มปลายทางอย่างน้อย 1 จุด'
                    );

                    return false;
                }

                if (
                    !startInput.value.trim()
                ) {
                    showError(
                        'กรุณาเลือกจุดเริ่มต้น'
                    );

                    startInput.focus();

                    return false;
                }

                const incompleteSegment =
                    segments.find(
                        function (segment) {
                            return !segment.destinationInput.value.trim();
                        }
                    );

                if (
                    incompleteSegment
                ) {
                    const index =
                        getSegmentIndex(
                            incompleteSegment.id
                        );

                    showError(
                        `กรุณาระบุปลายทางช่วงที่ ${index + 1}`
                    );

                    incompleteSegment.destinationInput.focus();

                    return false;
                }

                routeCalculationVersion += 1;

                const calculationVersion =
                    routeCalculationVersion;

                clearError();

                calculateAllRoutesButton.disabled =
                    true;

                addSegmentButton.disabled =
                    true;

                try {
                    for (
                        let index = 0;
                        index < segments.length;
                        index += 1
                    ) {
                        const segment =
                            segments[
                                index
                            ];

                        showStatus(
                            `กำลังคำนวณช่วงที่ ${index + 1} จาก ${segments.length} ช่วง...`
                        );

                        const completed =
                            await calculateSegmentRoute(
                                segment,

                                calculationVersion
                            );

                        if (
                            !completed
                        ) {
                            return false;
                        }
                    }

                    refreshMapMarkers();

                    fitRoutesToMap();

                    renderMapLegend();

                    updateTotals();

                    if (
                        !errorElement.classList.contains(
                            'd-none'
                        )
                    ) {
                        return false;
                    }

                    showStatus(
                        `คำนวณครบ ${segments.length} ช่วง ระยะทางรวม ${distanceInput.value || '0.00'} กม.`
                    );

                    return true;
                } catch (error) {
                    showError(
                        normalizeDirectionsError(
                            error
                        )
                    );

                    console.error(
                        'Google Directions error:',

                        error
                    );

                    return false;
                } finally {
                    calculateAllRoutesButton.disabled =
                        false;

                    addSegmentButton.disabled =
                        false;
                }
            }

            function renderMapLegend() {
                mapLegend.innerHTML =
                    '';

                segments.forEach(
                    function (
                        segment,

                        index
                    ) {
                        const item =
                            document.createElement(
                                'div'
                            );

                        item.className =
                            'map-legend-item';

                        const dot =
                            document.createElement(
                                'span'
                            );

                        dot.className =
                            'map-legend-dot';

                        dot.style.backgroundColor =
                            getRouteColor(
                                index
                            );

                        const label =
                            document.createElement(
                                'span'
                            );

                        label.textContent =
                            `ช่วงที่ ${index + 1}`;

                        item.appendChild(
                            dot
                        );

                        item.appendChild(
                            label
                        );

                        mapLegend.appendChild(
                            item
                        );
                    }
                );
            }

            async function assignStartPlace(
                label,

                location
            ) {
                startInput.value =
                    label;

                startLocation =
                    location;

                invalidateSegmentAndFollowing(
                    0
                );

                clearError();

                setActiveField(
                    'destination',

                    segments[0]?.id || null
                );

                refreshMapMarkers();

                if (
                    map &&
                    location
                ) {
                    map.panTo(
                        location
                    );

                    map.setZoom(
                        15
                    );
                }

                if (
                    segments.length &&
                    segments.every(
                        function (segment) {
                            return Boolean(
                                segment.destinationInput.value.trim()
                            );
                        }
                    )
                ) {
                    await calculateAllRoutes();
                } else {
                    showStatus(
                        'เลือกจุดเริ่มต้นแล้ว กรุณาเลือกสถานที่ถัดไป'
                    );
                }
            }

            async function assignSegmentPlace(
                segment,

                label,

                location
            ) {
                const index =
                    getSegmentIndex(
                        segment.id
                    );

                segment.destinationInput.value =
                    label;

                segment.destinationLocation =
                    location;

                invalidateSegmentAndFollowing(
                    index
                );

                clearError();

                refreshMapMarkers();

                if (
                    map &&
                    location
                ) {
                    map.panTo(
                        location
                    );

                    map.setZoom(
                        15
                    );
                }

                const nextSegment =
                    segments[
                        index + 1
                    ];

                if (
                    nextSegment
                ) {
                    setActiveField(
                        'destination',

                        nextSegment.id
                    );
                } else {
                    setActiveField(
                        'destination',

                        segment.id
                    );
                }

                if (
                    startInput.value.trim() &&
                    segments.every(
                        function (item) {
                            return Boolean(
                                item.destinationInput.value.trim()
                            );
                        }
                    )
                ) {
                    await calculateAllRoutes();
                } else {
                    showStatus(
                        `เลือกปลายทางช่วงที่ ${index + 1} แล้ว`
                    );
                }
            }

            function initializeStartAutocomplete() {
                if (
                    !map ||
                    !google.maps.places
                ) {
                    return;
                }

                startAutocomplete =
                    new google.maps.places.Autocomplete(
                        startInput,

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

                startAutocomplete.bindTo(
                    'bounds',

                    map
                );

                startAutocomplete.addListener(
                    'place_changed',

                    async function () {
                        const place =
                            startAutocomplete.getPlace();

                        if (
                            !place?.geometry?.location
                        ) {
                            showError(
                                'สถานที่นี้ไม่มีข้อมูลพิกัด กรุณาเลือกสถานที่ใหม่'
                            );

                            return;
                        }

                        await assignStartPlace(
                            buildPlaceLabel(
                                place
                            ),

                            place.geometry.location
                        );
                    }
                );
            }

            function initializeSegmentAutocomplete(
                segment
            ) {
                if (
                    !map ||
                    !google.maps.places ||
                    segment.autocomplete
                ) {
                    return;
                }

                const autocomplete =
                    new google.maps.places.Autocomplete(
                        segment.destinationInput,

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

                    async function () {
                        const place =
                            autocomplete.getPlace();

                        if (
                            !place?.geometry?.location
                        ) {
                            showError(
                                'สถานที่นี้ไม่มีข้อมูลพิกัด กรุณาเลือกสถานที่ใหม่'
                            );

                            return;
                        }

                        await assignSegmentPlace(
                            segment,

                            buildPlaceLabel(
                                place
                            ),

                            place.geometry.location
                        );
                    }
                );

                segment.autocomplete =
                    autocomplete;
            }

            async function assignCoordinates(
                position
            ) {
                const coordinates =
                    formatCoordinates(
                        position
                    );

                if (
                    activeField.type === 'start'
                ) {
                    await assignStartPlace(
                        coordinates,

                        position
                    );

                    return;
                }

                const segment =
                    getSegment(
                        activeField.segmentId
                    );

                if (
                    !segment
                ) {
                    showError(
                        'กรุณาเลือกช่องปลายทางที่ต้องการก่อน'
                    );

                    return;
                }

                await assignSegmentPlace(
                    segment,

                    coordinates,

                    position
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
                        fallbackPosition
                    );

                    return;
                }

                const selectedField = {
                    ...activeField,
                };

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

                    async function (
                        place,

                        status
                    ) {
                        if (
                            status !==
                                google.maps.places.PlacesServiceStatus.OK ||
                            !place?.geometry?.location
                        ) {
                            activeField =
                                selectedField;

                            await assignCoordinates(
                                fallbackPosition
                            );

                            return;
                        }

                        const label =
                            buildPlaceLabel(
                                place
                            );

                        const location =
                            place.geometry.location;

                        if (
                            selectedField.type === 'start'
                        ) {
                            await assignStartPlace(
                                label,

                                location
                            );

                            return;
                        }

                        const segment =
                            getSegment(
                                selectedField.segmentId
                            );

                        if (
                            !segment
                        ) {
                            return;
                        }

                        await assignSegmentPlace(
                            segment,

                            label,

                            location
                        );
                    }
                );
            }

            function handleMapClick(
                event
            ) {
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
                    event.latLng
                );
            }

            function resetAllRoutes() {
                routeCalculationVersion += 1;

                startInput.value =
                    '';

                startLocation =
                    null;

                segments.forEach(
                    function (segment) {
                        clearSegmentRoute(
                            segment
                        );

                        segment.destinationInput.value =
                            '';

                        segment.destinationLocation =
                            null;

                        segment.weightInput.value =
                            '0';

                        updateSegmentPreview(
                            segment
                        );
                    }
                );

                while (
                    segments.length > 1
                ) {
                    const lastSegment =
                        segments[
                            segments.length - 1
                        ];

                    if (
                        lastSegment.autocomplete &&
                        window.google?.maps?.event
                    ) {
                        google.maps.event.clearInstanceListeners(
                            lastSegment.autocomplete
                        );
                    }

                    lastSegment.card.remove();

                    segments.pop();
                }

                removeMapMarkers();

                refreshSegmentIndexes();

                updateTotals();

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

            function validateBeforeSubmit() {
                if (
                    !startInput.value.trim()
                ) {
                    throw new Error(
                        'กรุณาระบุจุดเริ่มต้น'
                    );
                }

                if (
                    !segments.length
                ) {
                    throw new Error(
                        'กรุณาเพิ่มปลายทางอย่างน้อย 1 จุด'
                    );
                }

                segments.forEach(
                    function (
                        segment,

                        index
                    ) {
                        if (
                            !segment.destinationInput.value.trim()
                        ) {
                            throw new Error(
                                `กรุณาระบุปลายทางช่วงที่ ${index + 1}`
                            );
                        }

                        const distance =
                            Number.parseFloat(
                                segment.distanceInput.value
                            );

                        getSegmentFuelInformation(
                            segment,

                            distance
                        );
                    }
                );

                updateLegacyFields();

                updateTotals();
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

                        initializeStartAutocomplete();

                        segments.forEach(
                            function (segment) {
                                initializeSegmentAutocomplete(
                                    segment
                                );
                            }
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
                        segments.length &&
                        segments.every(
                            function (segment) {
                                return Boolean(
                                    segment.destinationInput.value.trim()
                                );
                            }
                        )
                    ) {
                        calculateAllRoutes();
                    }
                };

            window.gm_authFailure =
                function () {
                    showError(
                        'Google Maps โหลดไม่ได้ กรุณาตรวจสอบ API Key, Billing และ Website restrictions'
                    );
                };

            initialSegments.forEach(
                function (segmentData) {
                    createSegment(
                        segmentData
                    );
                }
            );

            if (
                !segments.length
            ) {
                createSegment();
            }

            startInput.addEventListener(
                'focus',

                function () {
                    setActiveField(
                        'start'
                    );
                }
            );

            startInput.addEventListener(
                'input',

                function () {
                    startLocation =
                        null;

                    invalidateSegmentAndFollowing(
                        0
                    );

                    clearError();

                    clearStatus();
                }
            );

            addSegmentButton.addEventListener(
                'click',

                function () {
                    const lastSegment =
                        segments[
                            segments.length - 1
                        ];

                    if (
                        lastSegment &&
                        !lastSegment.destinationInput.value.trim()
                    ) {
                        showError(
                            'กรุณาระบุปลายทางของช่วงล่าสุดก่อนเพิ่มสถานที่ถัดไป'
                        );

                        lastSegment.destinationInput.focus();

                        return;
                    }

                    clearError();

                    const segment =
                        createSegment({
                            load_weight: 0,
                        });

                    setActiveField(
                        'destination',

                        segment.id
                    );

                    segment.destinationInput.focus();

                    showStatus(
                        `เพิ่มช่วงที่ ${segments.length} แล้ว`
                    );
                }
            );

            calculateAllRoutesButton.addEventListener(
                'click',

                calculateAllRoutes
            );

            clearRoutesButton.addEventListener(
                'click',

                resetAllRoutes
            );

            truckSelect.addEventListener(
                'change',

                function () {
                    updateTruckInformation();

                    clearError();

                    updateAllFuelCalculations({
                        showErrors: true,
                    });
                }
            );

            // เมื่อเปลี่ยนวันที่บันทึก ให้คำนวณอายุรถและทุกช่วงใหม่
            dateRecordInput.addEventListener(
                'change',

                function () {
                    updateTruckInformation();

                    clearError();

                    updateAllFuelCalculations({
                        showErrors: true,
                    });
                }
            );

            fuelPriceInput.addEventListener(
                'input',

                function () {
                    clearError();

                    updateAllFuelCalculations({
                        showErrors: true,
                    });
                }
            );

            form.addEventListener(
                'keydown',

                function (event) {
                    if (
                        event.key === 'Enter' &&
                        event.target !== startInput &&
                        !event.target.classList.contains(
                            'segment-destination'
                        )
                    ) {
                        event.preventDefault();
                    }
                }
            );

            form.addEventListener(
                'submit',

                function (event) {
                    try {
                        validateBeforeSubmit();
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

            segments.forEach(
                function (segment) {
                    updateSegmentPreview(
                        segment
                    );

                    calculateSegmentFuel(
                        segment
                    );
                }
            );

            updateTotals();

            renderMapLegend();
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
