@extends('layout')

@section('namepage')
    <div class="container">
        <h3>
            รายละเอียดบันทึกน้ำมันรถบรรทุก
        </h3>
    </div>
@endsection

@section('content')
    <style>
        .fuel-detail-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background-color: #fff;
        }

        .fuel-detail-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .fuel-detail-value {
            font-weight: 600;
            color: #212529;
        }

        .fuel-route-point {
            min-width: 180px;
            max-width: 260px;
            white-space: normal;
        }

        .fuel-segment-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #eef5ff;
            color: #0d6efd;
            font-weight: 600;
        }
    </style>

    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <a
                href="{{ route('fuel_records.index') }}"
                class="btn btn-outline-secondary"
            >
                กลับหน้ารายการ
            </a>

            @if (in_array(auth()->user()->role, ['admin', 'staff'], true))
                <a
                    href="{{ route(
                        'fuel_records.edit',
                        $fuel_record->id_fuel_record
                    ) }}"
                    class="btn btn-primary"
                >
                    แก้ไข
                </a>
            @endif
        </div>

        <div class="fuel-detail-card p-4 shadow-sm mb-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        วันที่
                    </div>

                    <div class="fuel-detail-value">
                        {{
                            $fuel_record->date_record
                                ? $fuel_record->date_record->format('d/m/Y')
                                : '-'
                        }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        ทะเบียนรถ
                    </div>

                    <div class="fuel-detail-value">
                        {{ $fuel_record->trucks_id_truck }}
                    </div>

                    @if ($fuel_record->truck?->province_truck)
                        <small class="text-muted">
                            {{ $fuel_record->truck->province_truck }}
                        </small>
                    @endif
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        ยี่ห้อรถ
                    </div>

                    <div class="fuel-detail-value">
                        {{
                            $fuel_record->truck?->brand?->name_brand
                            ?? $fuel_record->truck?->brand_truck
                            ?? '-'
                        }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        ราคาน้ำมัน
                    </div>

                    <div class="fuel-detail-value">
                        {{ number_format((float) $fuel_record->cost_fuel, 2) }}
                        บาท/ลิตร
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="fuel-detail-label">
                        จุดเริ่มต้น
                    </div>

                    <div class="fuel-detail-value">
                        {{ $fuel_record->start_point }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="fuel-detail-label">
                        ปลายทางสุดท้าย
                    </div>

                    <div class="fuel-detail-value">
                        {{ $fuel_record->destination }}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        จำนวนช่วง
                    </div>

                    <div class="fuel-detail-value">
                        {{ $fuel_record->segments->count() }}
                        ช่วง
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        ระยะทางรวม
                    </div>

                    <div class="fuel-detail-value">
                        {{ number_format((float) $fuel_record->distance, 2) }}
                        กม.
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        น้ำมันรวม
                    </div>

                    <div class="fuel-detail-value">
                        @if ($fuel_record->total_fuel_liters !== null)
                            {{ number_format((float) $fuel_record->total_fuel_liters, 2) }}
                            ลิตร
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        ค่าน้ำมันรวม
                    </div>

                    <div class="fuel-detail-value text-primary">
                        {{ number_format((float) $fuel_record->cost_fuel_total, 2) }}
                        บาท
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        น้ำหนักบรรทุกสูงสุดของรถ
                    </div>

                    <div class="fuel-detail-value">
                        {{
                            number_format(
                                (float) (
                                    $fuel_record->max_load
                                    ?? $fuel_record->truck?->weight_truck
                                ),
                                2
                            )
                        }}
                        กก.
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="fuel-detail-label">
                        น้ำหนักสูงสุดในรายการ
                    </div>

                    <div class="fuel-detail-value">
                        {{ number_format((float) $fuel_record->current_weight, 2) }}
                        กก.
                    </div>
                </div>
            </div>
        </div>

        <div class="fuel-detail-card shadow-sm overflow-hidden">
            <div class="p-3 border-bottom">
                <h5 class="mb-0">
                    รายละเอียดแต่ละช่วงเดินทาง
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">
                                ช่วง
                            </th>

                            <th>
                                ต้นทาง
                            </th>

                            <th>
                                ปลายทาง
                            </th>

                            <th class="text-end">
                                น้ำหนักบรรทุก
                            </th>

                            <th class="text-end">
                                ระยะทาง
                            </th>

                            <th class="text-end">
                                อัตราสิ้นเปลือง
                            </th>

                            <th class="text-end">
                                น้ำมันที่ใช้
                            </th>

                            <th class="text-end">
                                ค่าน้ำมัน
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($fuel_record->segments as $segment)
                            <tr>
                                <td class="text-center">
                                    <span class="fuel-segment-number">
                                        {{ $segment->sequence }}
                                    </span>
                                </td>

                                <td>
                                    <div class="fuel-route-point">
                                        {{ $segment->start_point }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fuel-route-point">
                                        {{ $segment->destination }}
                                    </div>
                                </td>

                                <td class="text-end">
                                    @if ((float) $segment->load_weight > 0)
                                        {{ number_format((float) $segment->load_weight, 2) }}
                                        กก.
                                    @else
                                        <span class="badge text-bg-secondary">
                                            รถเปล่า
                                        </span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    {{ number_format((float) $segment->distance, 2) }}
                                    กม.
                                </td>

                                <td class="text-end">
                                    {{ number_format((float) $segment->fuel_rate, 2) }}
                                    กม./ลิตร
                                </td>

                                <td class="text-end">
                                    {{ number_format((float) $segment->fuel_liters, 2) }}
                                    ลิตร
                                </td>

                                <td class="text-end fw-semibold">
                                    {{ number_format((float) $segment->fuel_cost, 2) }}
                                    บาท
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="8"
                                    class="text-center text-muted py-4"
                                >
                                    รายการนี้ไม่มีข้อมูลช่วงเดินทาง
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($fuel_record->segments->isNotEmpty())
                        <tfoot class="table-light">
                            <tr>
                                <th
                                    colspan="4"
                                    class="text-end"
                                >
                                    รวมทั้งหมด
                                </th>

                                <th class="text-end">
                                    {{ number_format((float) $fuel_record->distance, 2) }}
                                    กม.
                                </th>

                                <th></th>

                                <th class="text-end">
                                    {{ number_format((float) $fuel_record->total_fuel_liters, 2) }}
                                    ลิตร
                                </th>

                                <th class="text-end">
                                    {{ number_format((float) $fuel_record->cost_fuel_total, 2) }}
                                    บาท
                                </th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection

