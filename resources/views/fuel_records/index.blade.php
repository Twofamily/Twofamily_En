@extends('layout')

@section('namepage')
    <div class="container">
        <h3>
            บันทึกน้ำมันรถบรรทุก
        </h3>
    </div>
@endsection

@section('content')
    <style>
        .fuel-filter-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background-color: #fff;
        }

        .fuel-action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 8px;
        }

        .fuel-action-button svg {
            width: 17px;
            height: 17px;
        }

        .fuel-location {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .fuel-table th,
        .fuel-table td {
            white-space: nowrap;
        }

        .fuel-segment-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            background-color: #eef5ff;
            color: #0d6efd;
            font-size: 12px;
            font-weight: 600;
        }
    </style>

    <div class="container py-3">


        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="text-muted">
                พบทั้งหมด {{ number_format($records->total()) }} รายการ
            </div>

            @if (in_array(auth()->user()->role, ['admin', 'staff'], true))
                <a href="{{ route('fuel_records.create') }}" class="btn btn-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2" class="me-1" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14" />
                    </svg>

                    เพิ่มบันทึกน้ำมัน
                </a>
            @endif
        </div>

        <x-search-bar :action="route('fuel_records.index')"
                      placeholder="ค้นหาทะเบียนรถ / ยี่ห้อ / จุดเริ่มต้น / ปลายทาง"
                      :search="$search" :per-page="$perPage"
                      :dates="true" :date-from="$dateFrom" :date-to="$dateTo" date-label="วันที่"
                      :ranges="$ranges" />

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0 fuel-table">
                <thead class="table-light">
                    <tr>
                        <th>
                            วันที่
                        </th>

                        <th>
                            รถบรรทุก
                        </th>

                        <th>
                            จุดเริ่มต้น
                        </th>

                        <th>
                            ปลายทาง
                        </th>

                        <th class="text-center">
                            ช่วง
                        </th>

                        <th class="text-end">
                            ระยะทาง
                        </th>

                        <th class="text-end">
                            ราคาน้ำมัน
                        </th>

                        <th class="text-end">
                            น้ำมันรวม
                        </th>

                        <th class="text-end">
                            ค่าน้ำมันทั้งหมด
                        </th>

                        <th class="text-center" style="width:80px;">
                            จัดการ
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                {{ $record->date_record ? $record->date_record->format('d/m/Y') : '-' }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $record->trucks_id_truck }}
                                </div>

                                <small class="text-muted">
                                    {{ $record->truck?->brand?->name_brand ?? ($record->truck?->province_truck ?? '-') }}
                                </small>
                            </td>

                            <td>
                                <div class="fuel-location" title="{{ $record->start_point }}">
                                    {{ $record->start_point }}
                                </div>
                            </td>

                            <td>
                                <div class="fuel-location" title="{{ $record->destination }}">
                                    {{ $record->destination }}
                                </div>
                            </td>

                            <td class="text-center">
                                @if ($record->segments_count > 0)
                                    <span class="fuel-segment-badge">
                                        {{ $record->segments_count }}
                                    </span>
                                @else
                                    <span class="text-muted">
                                        -
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                {{ number_format((float) $record->distance, 2) }}

                                <small class="text-muted">
                                    กม.
                                </small>
                            </td>

                            <td class="text-end">
                                {{ number_format((float) $record->cost_fuel, 2) }}

                                <small class="text-muted">
                                    บาท/ลิตร
                                </small>
                            </td>

                            <td class="text-end">
                                @if ($record->total_fuel_liters !== null)
                                    {{ number_format((float) $record->total_fuel_liters, 2) }}

                                    <small class="text-muted">
                                        ลิตร
                                    </small>
                                @else
                                    <span class="text-muted">
                                        -
                                    </span>
                                @endif
                            </td>

                            <td class="text-end fw-semibold">
                                {{ number_format((float) $record->cost_fuel_total, 2) }}

                                <small class="text-muted">
                                    บาท
                                </small>
                            </td>

                            <td class="text-center">
                                @php($canEditFuel = in_array(auth()->user()->role, ['admin', 'staff'], true))
                                <x-row-actions :show="route('fuel_records.show', $record->id_fuel_record)"
                                               :edit="$canEditFuel ? route('fuel_records.edit', $record->id_fuel_record) : null"
                                               :delete="$canEditFuel ? route('fuel_records.destroy', $record->id_fuel_record) : null"
                                               label="บันทึกน้ำมันรายการนี้"
                                               delete-confirm="ต้องการลบข้อมูลบันทึกน้ำมันรายการนี้หรือไม่" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                @if ($search !== '' || $dateFrom || $dateTo)
                                    ไม่พบข้อมูลตามเงื่อนไขที่ค้นหา
                                @else
                                    — ไม่พบข้อมูล —
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>

        @include('partials.pagination-footer', ['paginator' => $records])
    </div>
@endsection
