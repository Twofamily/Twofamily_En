@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ข้อมูลพนักงานขับรถ</h3>
    </div>
@endsection

@section('content')
    <style>
        .detail-card {
            max-width: 900px;
            margin-inline: auto;
            border: 1px solid rgba(0, 0, 0, .08);
            box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
            border-radius: 14px;
        }

        .label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .value {
            font-size: 1.05rem;
        }

        .driver-img {
            max-height: 320px;
            object-fit: cover;
            width: 100%;
        }
    </style>

    <div class="card detail-card overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-4 align-items-center">
                <!-- คอลัมน์ซ้าย: รูปภาพใบขับขี่ -->
                <div class="col-md-5 text-center">
                    @if ($driver->citizen_image)
                        <img src="{{ asset('storage/' . $driver->citizen_image) }}"
                            class="img-fluid rounded shadow-sm border driver-img" alt="รูปใบขับขี่">
                    @else
                        <div class="p-4 border rounded bg-light text-muted d-flex align-items-center justify-content-center"
                            style="min-height: 220px;">
                            ไม่มีรูปภาพใบขับขี่
                        </div>
                    @endif
                </div>

                <!-- คอลัมน์ขวา: รายละเอียดข้อมูลพนักงาน -->
                <div class="col-md-7">
                    <div class="mb-3">
                        <div class="label">ชื่อ-สกุล</div>
                        <div class="value fw-bold text-dark">
                            {{ $driver->fname_driver }} {{ $driver->lname_driver }}
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="label">เบอร์โทร</div>
                            <div class="value">{{ $driver->phone_driver ?: '-' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="label">เลขบัตรประชาชน</div>
                            <div class="value">{{ $driver->citizenid_driver ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="label">ที่อยู่</div>
                        <div class="value">
                            {{ $driver->address_no ? 'บ้านเลขที่ ' . $driver->address_no : '' }}
                            {{ $driver->moo ? 'หมู่ ' . $driver->moo : '' }}
                            {{ $driver->address_detail }}
                            {{ $driver->subdistrict ? 'ต.' . $driver->subdistrict : '' }}
                            {{ $driver->district ? 'อ.' . $driver->district : '' }}
                            {{ $driver->province ? 'จ.' . $driver->province : '' }}
                            {{ $driver->zipcode }}
                        </div>
                    </div>

                    {{-- แคมป์ที่ทำงาน (ผ่านการมอบหมายรถ) --}}
                    @php $current = $driver->camps->firstWhere('pivot.released_date', null); @endphp
                    <div class="mb-4">
                        <div class="label">ประจำแคมป์ตอนนี้</div>
                        <div class="value">
                            @if ($current)
                                <a href="{{ route('camps.show', $current) }}" class="text-decoration-none">
                                    {{ $current->code_camp }} — {{ $current->name_camp }}
                                </a>
                                <span class="text-muted small">· ขับรถ {{ $current->pivot->id_truck }}</span>
                            @else
                                <span class="text-muted">ว่าง (ไม่ได้ประจำแคมป์ไหน)</span>
                            @endif
                        </div>
                    </div>

                    <div class="pt-2 d-flex gap-2">
                        <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary">
                            ย้อนกลับ
                        </a>

                        <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-outline-primary">
                            แก้ไข
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ประวัติการทำงานตามแคมป์ --}}
    <div class="card detail-card mt-4">
        <div class="card-body p-4">
            <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">ประวัติการทำงานตามแคมป์</h6>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>แคมป์</th>
                            <th>ลูกค้า</th>
                            <th>รถ</th>
                            <th>ช่วงเวลา</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($driver->camps as $camp)
                            <tr>
                                <td>
                                    <a href="{{ route('camps.show', $camp) }}" class="text-decoration-none">
                                        {{ $camp->code_camp }}
                                    </a>
                                    <div class="small text-muted">{{ $camp->name_camp }}</div>
                                </td>
                                <td>{{ $camp->customer->name_customer ?? '-' }}</td>
                                <td>{{ $camp->pivot->id_truck }}</td>
                                <td>
                                    {{ thai_date($camp->pivot->assigned_date) }} –
                                    @if ($camp->pivot->released_date)
                                        {{ thai_date($camp->pivot->released_date) }}
                                    @else
                                        <span class="badge bg-success">ปัจจุบัน</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">ยังไม่เคยประจำแคมป์ไหน</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection