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
        @if (session('ok'))
            <div
                class="alert alert-success alert-dismissible fade show"
                role="alert"
            >
                {{ session('ok') }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="ปิด"
                ></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="text-muted">
                พบทั้งหมด {{ number_format($records->total()) }} รายการ
            </div>

            @if (in_array(auth()->user()->role, ['admin', 'staff'], true))
                <a
                    href="{{ route('fuel_records.create') }}"
                    class="btn btn-dark"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="17"
                        height="17"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                        class="me-1"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 5v14m-7-7h14"
                        />
                    </svg>

                    เพิ่มบันทึกน้ำมัน
                </a>
            @endif
        </div>

        <div class="fuel-filter-card p-3 shadow-sm mb-3">
            <form
                method="GET"
                action="{{ route('fuel_records.index') }}"
            >
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4 col-md-6">
                        <label
                            for="q"
                            class="form-label"
                        >
                            ค้นหา
                        </label>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="form-control"
                            placeholder="ต้นทาง ปลายทาง หรือทะเบียนรถ"
                            value="{{ $q }}"
                        >
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label
                            for="trucks_id"
                            class="form-label"
                        >
                            ทะเบียนรถ
                        </label>

                        <select
                            id="trucks_id"
                            name="trucks_id"
                            class="form-select"
                        >
                            <option value="">
                                ทะเบียนทั้งหมด
                            </option>

                            @foreach ($trucks as $truck)
                                <option
                                    value="{{ $truck->id_truck }}"
                                    @selected(
                                        $trucks_id === (string) $truck->id_truck
                                    )
                                >
                                    {{ $truck->id_truck }}

                                    @if ($truck->province_truck)
                                        - {{ $truck->province_truck }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label
                            for="date_from"
                            class="form-label"
                        >
                            ตั้งแต่วันที่
                        </label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="form-control"
                            value="{{ $date_from }}"
                        >
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label
                            for="date_to"
                            class="form-label"
                        >
                            ถึงวันที่
                        </label>

                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            class="form-control"
                            value="{{ $date_to }}"
                        >
                    </div>

                    <div class="col-lg-1 col-md-6">
                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                            title="ค้นหา"
                            aria-label="ค้นหา"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="8"
                                />

                                <path
                                    stroke-linecap="round"
                                    d="m21 21-4.35-4.35"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                @if ($q !== '' || $trucks_id !== '' || $date_from || $date_to)
                    <div class="mt-3">
                        <a
                            href="{{ route('fuel_records.index') }}"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            ล้างตัวกรอง
                        </a>
                    </div>
                @endif
            </form>
        </div>

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

                        <th class="text-center">
                            จัดการ
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                {{
                                    $record->date_record
                                        ? $record->date_record->format('d/m/Y')
                                        : '-'
                                }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $record->trucks_id_truck }}
                                </div>

                                <small class="text-muted">
                                    {{
                                        $record->truck?->brand?->name_brand
                                        ?? $record->truck?->province_truck
                                        ?? '-'
                                    }}
                                </small>
                            </td>

                            <td>
                                <div
                                    class="fuel-location"
                                    title="{{ $record->start_point }}"
                                >
                                    {{ $record->start_point }}
                                </div>
                            </td>

                            <td>
                                <div
                                    class="fuel-location"
                                    title="{{ $record->destination }}"
                                >
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

                            <td>
                                <div class="d-flex justify-content-center gap-2 flex-nowrap">
                                    <a
                                        href="{{ route(
                                            'fuel_records.show',
                                            $record->id_fuel_record
                                        ) }}"
                                        class="btn btn-outline-info fuel-action-button"
                                        title="ดูรายละเอียด"
                                        aria-label="ดูรายละเอียด"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z"
                                            />

                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="3"
                                            />
                                        </svg>
                                    </a>

                                    @if (in_array(auth()->user()->role, ['admin', 'staff'], true))
                                        <a
                                            href="{{ route(
                                                'fuel_records.edit',
                                                $record->id_fuel_record
                                            ) }}"
                                            class="btn btn-outline-primary fuel-action-button"
                                            title="แก้ไข"
                                            aria-label="แก้ไข"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487Zm0 0L19.5 7.125"
                                                />

                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 13.5v6h-15v-15h6"
                                                />
                                            </svg>
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'fuel_records.destroy',
                                                $record->id_fuel_record
                                            ) }}"
                                            class="m-0"
                                            data-confirm="ต้องการลบข้อมูลบันทึกน้ำมันรายการนี้หรือไม่"
                                            data-confirm-title="ยืนยันการลบข้อมูล"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล"
                                            onsubmit="return window.confirmDeleteHandled
                                                ? true
                                                : confirm('ยืนยันการลบข้อมูล?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-outline-danger fuel-action-button"
                                                title="ลบ"
                                                aria-label="ลบ"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.108 48.108 0 0 1 3.478-.397m7.5 0V4.477c0-1.18-.91-2.166-2.09-2.203a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.203v.916m7.5 0a48.67 48.67 0 0 0-7.5 0"
                                                    />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="10"
                                class="text-center text-muted py-4"
                            >
                                @if ($q !== '' || $trucks_id !== '' || $date_from || $date_to)
                                    ไม่พบข้อมูลตามเงื่อนไขที่ค้นหา
                                @else
                                    — ไม่พบข้อมูล —
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($records->hasPages())
                <div class="p-3">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection