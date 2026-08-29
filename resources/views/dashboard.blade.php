@extends('layout')

@section('namepage')
    <div class="container">
        <h3 class="mb-0">แดชบอร์ด</h3>
    </div>
@endsection

@section('content')
    <style>
        .dashboard-page {
            --card-border: rgba(0, 0, 0, .06);
            --card-shadow-lg: 0 10px 28px rgba(0, 0, 0, .06);
            --card-shadow-md: 0 8px 24px rgba(0, 0, 0, .05);
            --card-radius: 18px;
            --primary: #1a1a1a;
        }

        .dashboard-page .kpi-card,
        .dashboard-page .section-card {
            background-color: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: var(--card-radius);
        }

        .dashboard-page .kpi-card { box-shadow: var(--card-shadow-lg); }
        .dashboard-page .section-card { box-shadow: var(--card-shadow-md); }

        .dashboard-page .kpi-number {
            font-size: 2.1rem;
            font-weight: 700;
            line-height: 1.1;
            color: var(--primary);
        }

        .dashboard-page .kpi-label { font-size: .875rem; }

        .dashboard-page .table thead th {
            font-weight: 600;
            color: var(--primary);
        }

        .dashboard-page .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, .04);
        }

        .dashboard-page .btn-dark {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .dashboard-page .btn-dark:hover {
            background-color: #333333;
            border-color: #333333;
        }

        .dashboard-page .btn-outline-secondary {
            border-color: #d4d4d4;
            color: #404040;
        }

        .dashboard-page .btn-outline-secondary:hover {
            background-color: #e5e5e5;
            color: #171717;
        }

        .badge-soft {
            background: rgba(0, 0, 0, .05);
            color: #333;
        }
    </style>

    <div class="container py-3 dashboard-page">

        {{-- ===== KPI ===== --}}
        <div class="row g-3">

            <div class="col-12 col-md-6 col-xl-3">
                <div class="p-4 kpi-card h-100">
                    <div class="text-muted kpi-label">รถบรรทุกรวม</div>
                    <div class="kpi-number mt-1">{{ number_format($truckCounts['total']) }}</div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span class="badge text-bg-dark">พร้อมใช้งาน {{ $truckCounts['active'] }}</span>
                        <span class="badge text-bg-secondary">ซ่อมบำรุง {{ $truckCounts['maintenance'] }}</span>
                        <span class="badge text-bg-light text-dark">ปลดประจำการ {{ $truckCounts['retired'] }}</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="p-4 kpi-card h-100">
                    <div class="text-muted kpi-label">พนักงานขับรถ</div>
                    <div class="kpi-number mt-1">{{ number_format($driversTotal) }}</div>
                    <div class="mt-3">
                        <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary btn-sm">
                            ดูทั้งหมด
                        </a>
                    </div>
                </div>
            </div>

            @if ($hasCamps)
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="p-4 kpi-card h-100">
                        <div class="text-muted kpi-label">แคมป์ที่ดำเนินการอยู่</div>
                        <div class="kpi-number mt-1">{{ number_format($campsActive) }}</div>
                        <div class="mt-3 d-flex align-items-center gap-2">
                            <span class="badge badge-soft">ทั้งหมด {{ $campsTotal }}</span>
                            <a href="{{ route('camps.index') }}" class="btn btn-outline-secondary btn-sm">
                                ดูทั้งหมด
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-12 col-md-6 col-xl-3">
                <div class="p-4 kpi-card h-100">
                    <div class="text-muted kpi-label">ลูกค้าทั้งหมด</div>
                    <div class="kpi-number mt-1">{{ number_format($customersTotal) }}</div>
                    <div class="mt-3">
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                            ดูทั้งหมด
                        </a>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== ตาราง ===== --}}
        <div class="row g-3 mt-1">

            <div class="col-12 col-xl-6">
                <div class="p-4 section-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">แคมป์ที่ดำเนินการอยู่</h6>
                        @if ($hasCamps)
                            <a href="{{ route('camps.index') }}" class="btn btn-outline-secondary btn-sm">
                                ดูทั้งหมด
                            </a>
                        @endif
                    </div>

                    @if (count($activeCamps) === 0)
                        <p class="text-muted mb-0">ยังไม่มีแคมป์ที่ดำเนินการอยู่</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>แคมป์</th>
                                        <th>ลูกค้า</th>
                                        <th>พื้นที่</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($activeCamps as $c)
                                        <tr>
                                            <td>
                                                <a href="{{ route('camps.show', $c->id_camp) }}"
                                                   class="text-decoration-none text-dark fw-semibold">
                                                    {{ $c->name_camp }}
                                                </a>
                                                @if ($c->code_camp)
                                                    <div class="text-muted small">{{ $c->code_camp }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $c->customer_label ?? '-' }}</td>
                                            <td class="small">
                                                {{ $c->district ? $c->district . ' ' : '' }}{{ $c->province ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="p-4 section-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">รถที่เพิ่มล่าสุด</h6>
                        <a href="{{ route('trucks.index') }}" class="btn btn-outline-secondary btn-sm">
                            ดูทั้งหมด
                        </a>
                    </div>

                    @if (count($recentTrucks) === 0)
                        <p class="text-muted mb-0">ยังไม่มีข้อมูลรถ</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>จังหวัด</th>
                                        <th>ปี</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentTrucks as $t)
                                        <tr>
                                            <td>{{ $t->province_truck ?? '-' }}</td>
                                            <td>{{ $t->year_truck ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $map = [
                                                        'active'      => ['พร้อมใช้งาน', 'text-bg-dark'],
                                                        'maintenance' => ['ซ่อมบำรุง', 'text-bg-secondary'],
                                                        'retired'     => ['ปลดประจำการ', 'text-bg-light text-dark'],
                                                    ];
                                                    $label = $map[$t->status_truck ?? ''] ?? [$t->status_truck ?: '-', 'text-bg-light text-dark'];
                                                @endphp
                                                <span class="badge {{ $label[1] }}">{{ $label[0] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
@endsection