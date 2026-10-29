@extends('layout')

@section('namepage')
    <div class="container">
        <h3 class="mb-0">แดชบอร์ด</h3>
    </div>
@endsection

@section('content')
    @php
        $f = $revenue['filter'];
        $cur = $kpi['current'];
        $chg = $kpi['change'];
        $delta = function (?float $pct, bool $goodWhenUp = true) {
            if ($pct === null) {
                return '<span class="text-muted">ไม่มีข้อมูลช่วงก่อนให้เทียบ</span>';
            }
            $up = $pct >= 0;
            $good = $up === $goodWhenUp;
            $cls = abs($pct) < 0.05 ? 'text-muted' : ($good ? 'text-success' : 'text-danger');
            $arrow = $up ? '▲' : '▼';

            return '<span class="' .
                $cls .
                '">' .
                $arrow .
                ' ' .
                number_format(abs($pct), 1) .
                '%</span>' .
                ' <span class="text-muted">จากช่วงก่อน</span>';
        };

        $money = fn($v) => number_format((float) $v, 2);
    @endphp

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

        .dashboard-page .kpi-card {
            box-shadow: var(--card-shadow-lg);
        }

        .dashboard-page .section-card {
            box-shadow: var(--card-shadow-md);
        }

        .dashboard-page .kpi-number {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.15;
            color: var(--primary);
            font-variant-numeric: tabular-nums;
            word-break: break-all;
        }

        .dashboard-page .kpi-label {
            font-size: .875rem;
        }

        .dashboard-page .kpi-sub {
            font-size: .8rem;
        }

        .dashboard-page .table thead th {
            font-weight: 600;
            color: var(--primary);
            white-space: nowrap;
        }

        .dashboard-page .table td.num,
        .dashboard-page .table th.num {
            text-align: end;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .dashboard-page .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, .04);
        }

        .dashboard-page .btn-dark {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .dashboard-page .btn-dark:hover {
            background-color: #333;
            border-color: #333;
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

        .dashboard-page .chart-box {
            position: relative;
            height: 340px;
        }

        .dashboard-page .aging-bar {
            height: .5rem;
            border-radius: 1rem;
            background: rgba(0, 0, 0, .06);
            overflow: hidden;
        }

        .dashboard-page .aging-bar>div {
            height: 100%;
            border-radius: 1rem;
        }

        .dashboard-page .section-title {
            font-weight: 600;
            margin-bottom: .25rem;
        }

        .dashboard-page .section-note {
            font-size: .8rem;
            color: #737373;
        }
    </style>

    <div class="container py-3 dashboard-page" id="finance">

        @include('partials.dashboard-filter')

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mt-1">

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">รายได้ (ก่อน VAT)</div>
                    <div class="kpi-number mt-1">{{ $money($cur['revenue']) }}</div>
                    <div class="kpi-sub mt-2">บาท</div>
                </div>
            </div>

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">ต้นทุนรถ (น้ำมัน + ซ่อม)</div>
                    <div class="kpi-number mt-1">{{ $money($cur['cost']) }}</div>
                    <div class="kpi-sub mt-2">บาท</div>
                </div>
            </div>

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">กำไรขั้นต้น</div>
                    <div class="kpi-number mt-1 {{ $cur['profit'] < 0 ? 'text-danger' : '' }}">
                        {{ $money($cur['profit']) }}
                    </div>
                    <div class="kpi-sub mt-2">บาท</div>
                </div>
            </div>

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">เงินเข้าจริง (รวม VAT)</div>
                    <div class="kpi-number mt-1">{{ $money($cur['cashIn']) }}</div>
                    <div class="kpi-sub mt-2">บาท</div>
                </div>
            </div>

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">ลูกหนี้ค้างรับ ณ วันนี้</div>
                    <div class="kpi-number mt-1 {{ $kpi['receivable']['amount'] > 0 ? 'text-danger' : '' }}">
                        {{ $money($kpi['receivable']['amount']) }}
                    </div>
                    <div class="kpi-sub mt-2 d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge badge-soft">ใบแจ้งหนี้ค้าง {{ $kpi['receivable']['count'] }} ใบ</span>
                        <a href="{{ route('invoices.index') }}" class="small">ดูทั้งหมด</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            @include('partials.dashboard-chart')
        </div>

        <div class="p-4 section-card mt-3">
            <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                <div>
                    <div class="section-title">กำไรรายแคมป์</div>
                </div>
                <span class="badge badge-soft">{{ $f['label'] }}</span>
            </div>

            @if (!$campProfit['fuelHasCamp'])
                <div class="alert alert-warning small py-2">
                    ยังไม่ได้รัน <code>php artisan migrate</code> เพื่อเพิ่มแคมป์ให้บันทึกน้ำมัน
                    ตารางนี้จึงยังไม่มีค่าน้ำมัน
                </div>
            @endif

            @if ($campProfit['rows']->isEmpty())
                <p class="text-muted mb-0">ไม่มีรายได้หรือค่าน้ำมันของแคมป์ในช่วงนี้</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>แคมป์</th>
                                <th class="num">รายได้ (บาท)</th>
                                <th class="num">ค่าน้ำมัน (บาท)</th>
                                <th class="num">กำไร (บาท)</th>
                                {{-- <th></th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($campProfit['rows'] as $row)
                                <tr>
                                    <td>
                                        @if ($row['deleted'])
                                            <span class="fw-semibold">{{ $row['name'] }}</span>
                                        @else
                                            <a href="{{ route('camps.show', $row['id_camp']) }}"
                                                class="text-decoration-none text-dark fw-semibold">
                                                {{ $row['name'] }}
                                            </a>
                                        @endif
                                        <div class="text-muted small">{{ $row['code'] }} · {{ $row['customer'] }}
                                        </div>
                                    </td>
                                    <td class="num">{{ $money($row['revenue']) }}</td>
                                    <td class="num">{{ $money($row['fuel']) }}</td>
                                    <td class="num fw-semibold {{ $row['profit'] < 0 ? 'text-danger' : '' }}">
                                        {{ $money($row['profit']) }}
                                    </td>
                                    {{-- <td class="num">
                                        @if ($row['margin'] === null)
                                            <span class="badge text-bg-danger">ไม่มีรายได้</span>
                                        @endif
                                    </td> --}}
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold">
                                <td>รวมทุกแคมป์</td>
                                <td class="num">{{ $money($campProfit['totalRevenue']) }}</td>
                                <td class="num">{{ $money($campProfit['totalFuel']) }}</td>
                                <td class="num {{ $campProfit['totalProfit'] < 0 ? 'text-danger' : '' }}">
                                    {{ $money($campProfit['totalProfit']) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        <div class="p-4 section-card mt-3">
            <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                <div>
                    <div class="section-title">ต้นทุนรายรถ</div>
                </div>
                <span class="badge badge-soft">{{ $f['label'] }}</span>
            </div>

            @if ($truckCosts['rows']->isEmpty())
                <p class="text-muted mb-0">ไม่มีค่าน้ำมันหรือค่าซ่อมในช่วงนี้</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>รถ</th>
                                <th class="num">ระยะทาง</th>
                                <th class="num">น้ำมัน (บาท)</th>
                                <th class="num">ซ่อม (บาท)</th>
                                <th class="num">บาท/กม.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($truckCosts['rows'] as $t)
                                @php
                                    $high =
                                        $t['perKm'] !== null &&
                                        $truckCosts['avgPerKm'] &&
                                        $t['perKm'] > $truckCosts['avgPerKm'] * 1.2;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('trucks.show', $t['id_truck']) }}"
                                            class="text-decoration-none text-dark fw-semibold">{{ $t['id_truck'] }}</a>
                                        <div class="text-muted small">{{ $t['brand'] }} ·
                                            {{ $t['trips'] }} เที่ยว</div>
                                    </td>
                                    <td class="num">{{ number_format($t['distance'], 0) }} กม.</td>
                                    <td class="num">{{ $money($t['fuel']) }}</td>
                                    <td class="num">{{ $money($t['maintenance']) }}</td>
                                    <td class="num {{ $high ? 'text-danger fw-semibold' : '' }}">
                                        {{ $t['perKm'] !== null ? $money($t['perKm']) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($truckCosts['more'] > 0)
                    <div class="text-muted small mt-2">และอีก {{ $truckCosts['more'] }} คัน</div>
                @endif
            @endif
        </div>

        <div class="p-3 section-card mt-3 d-flex flex-wrap gap-3 gap-md-4 align-items-center small">
            <span class="text-muted">การปฏิบัติงานวันนี้</span>
            <a href="{{ route('trucks.index') }}" class="text-decoration-none text-dark">
                <i class="bi bi-truck me-1"></i>รถ {{ $ops['trucks'] }} คัน
                <span class="text-muted">(พร้อมใช้ {{ $ops['active'] }} · ซ่อม {{ $ops['maintenance'] }})</span>
            </a>
            <a href="{{ route('drivers.index') }}" class="text-decoration-none text-dark">
                <i class="bi bi-person-badge me-1"></i>คนขับ {{ $ops['drivers'] }} คน
            </a>
            <a href="{{ route('camps.index') }}" class="text-decoration-none text-dark">
                <i class="bi bi-geo-alt me-1"></i>แคมป์ที่เปิดอยู่ {{ $ops['camps'] }} แห่ง
            </a>
        </div>
    </div>
@endsection
