@extends('layout')

@section('namepage')
    <div class="container">
        <h3 class="mb-0">แดชบอร์ด</h3>
    </div>
@endsection

@section('content')
    @php
        $f    = $revenue['filter'];
        $cur  = $kpi['current'];
        $chg  = $kpi['change'];

        // ป้าย ▲▼ เทียบช่วงก่อน — $goodWhenUp = false สำหรับต้นทุน (ต้นทุนขึ้น = แย่)
        $delta = function (?float $pct, bool $goodWhenUp = true) {
            if ($pct === null) {
                return '<span class="text-muted">ไม่มีข้อมูลช่วงก่อนให้เทียบ</span>';
            }
            $up    = $pct >= 0;
            $good  = $up === $goodWhenUp;
            $cls   = abs($pct) < 0.05 ? 'text-muted' : ($good ? 'text-success' : 'text-danger');
            $arrow = $up ? '▲' : '▼';

            return '<span class="' . $cls . '">' . $arrow . ' ' . number_format(abs($pct), 1) . '%</span>'
                 . ' <span class="text-muted">จากช่วงก่อน</span>';
        };

        $money = fn ($v) => number_format((float) $v, 2);
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

        .dashboard-page .kpi-card { box-shadow: var(--card-shadow-lg); }
        .dashboard-page .section-card { box-shadow: var(--card-shadow-md); }

        .dashboard-page .kpi-number {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.15;
            color: var(--primary);
            font-variant-numeric: tabular-nums;
            word-break: break-all;
        }

        .dashboard-page .kpi-label { font-size: .875rem; }
        .dashboard-page .kpi-sub { font-size: .8rem; }

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

        .dashboard-page .table-hover tbody tr:hover { background-color: rgba(0, 0, 0, .04); }

        .dashboard-page .btn-dark { background-color: var(--primary); border-color: var(--primary); }
        .dashboard-page .btn-dark:hover { background-color: #333; border-color: #333; }
        .dashboard-page .btn-outline-secondary { border-color: #d4d4d4; color: #404040; }
        .dashboard-page .btn-outline-secondary:hover { background-color: #e5e5e5; color: #171717; }

        .badge-soft { background: rgba(0, 0, 0, .05); color: #333; }

        /* กล่องกราฟต้องมีความสูงคงที่ ไม่งั้น Chart.js จะยืดไม่หยุดตอน responsive */
        .dashboard-page .chart-box { position: relative; height: 340px; }

        .dashboard-page .aging-bar {
            height: .5rem;
            border-radius: 1rem;
            background: rgba(0, 0, 0, .06);
            overflow: hidden;
        }
        .dashboard-page .aging-bar > div { height: 100%; border-radius: 1rem; }

        .dashboard-page .section-title { font-weight: 600; margin-bottom: .25rem; }
        .dashboard-page .section-note { font-size: .8rem; color: #737373; }
    </style>

    <div class="container py-3 dashboard-page" id="finance">

        {{-- ===== ตัวกรองช่วงเวลา (คุมทุกตัวเลขที่เป็น "ตามช่วง") ===== --}}
        @include('partials.dashboard-filter')

        {{-- ===== การ์ดตัวเลขหลัก ===== --}}
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mt-1">

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">รายได้ (ก่อน VAT)</div>
                    <div class="kpi-number mt-1">{{ $money($cur['revenue']) }}</div>
                    <div class="kpi-sub mt-2">{!! $delta($chg['revenue']) !!}</div>
                </div>
            </div>

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">ต้นทุนรถ (น้ำมัน + ซ่อม)</div>
                    <div class="kpi-number mt-1">{{ $money($cur['cost']) }}</div>
                    <div class="kpi-sub mt-2">{!! $delta($chg['cost'], goodWhenUp: false) !!}</div>
                    <div class="kpi-sub text-muted">
                        น้ำมัน {{ $money($cur['fuel']) }} · ซ่อม {{ $money($cur['maintenance']) }}
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">กำไรขั้นต้น</div>
                    <div class="kpi-number mt-1 {{ $cur['profit'] < 0 ? 'text-danger' : '' }}">
                        {{ $money($cur['profit']) }}
                    </div>
                    <div class="kpi-sub mt-2">{!! $delta($chg['profit']) !!}</div>
                    <div class="kpi-sub text-muted">
                        @if ($cur['margin'] !== null)
                            อัตรากำไร {{ number_format($cur['margin'], 1) }}%
                        @else
                            ยังไม่มีรายได้ในช่วงนี้
                        @endif
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="p-3 p-xl-4 kpi-card h-100">
                    <div class="text-muted kpi-label">เงินเข้าจริง (รวม VAT)</div>
                    <div class="kpi-number mt-1">{{ $money($cur['cashIn']) }}</div>
                    <div class="kpi-sub mt-2">{!! $delta($chg['cashIn']) !!}</div>
                    <div class="kpi-sub text-muted">ไม่นับรายการหักเงินรับล่วงหน้า</div>
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

        <div class="text-muted small mt-2">
            กำไรขั้นต้น = รายได้ − ค่าน้ำมัน − ค่าซ่อม · ยังไม่รวมค่าแรงคนขับและราคาทุนวัสดุ
            · เทียบกับช่วง {{ $kpi['prevLabel'] }}
        </div>

        {{-- ===== กราฟรายได้ / ต้นทุน / กำไร ===== --}}
        <div class="mt-4">
            @include('partials.dashboard-chart')
        </div>

        {{-- ===== กำไรรายแคมป์ + ลูกหนี้ตามอายุหนี้ ===== --}}
        <div class="row g-3 mt-1">

            <div class="col-12 col-xl-7">
                <div class="p-4 section-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                        <div>
                            <div class="section-title">กำไรรายแคมป์</div>
                            <div class="section-note">
                                รายได้จากใบส่งของ − ค่าน้ำมันที่ระบุแคมป์ · เรียงจากกำไรน้อยที่สุด
                            </div>
                        </div>
                        <span class="badge badge-soft">{{ $f['label'] }}</span>
                    </div>

                    @if (! $campProfit['fuelHasCamp'])
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
                                        <th class="num">รายได้</th>
                                        <th class="num">ค่าน้ำมัน</th>
                                        <th class="num">กำไร</th>
                                        <th class="num">%</th>
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
                                                <div class="text-muted small">{{ $row['code'] }} · {{ $row['customer'] }}</div>
                                            </td>
                                            <td class="num">{{ $money($row['revenue']) }}</td>
                                            <td class="num">{{ $money($row['fuel']) }}</td>
                                            <td class="num fw-semibold {{ $row['profit'] < 0 ? 'text-danger' : '' }}">
                                                {{ $money($row['profit']) }}
                                            </td>
                                            <td class="num">
                                                @if ($row['margin'] === null)
                                                    <span class="badge text-bg-danger">ไม่มีรายได้</span>
                                                @else
                                                    <span class="{{ $row['margin'] < 0 ? 'text-danger' : ($row['margin'] < 20 ? 'text-warning-emphasis' : 'text-success') }}">
                                                        {{ number_format($row['margin'], 1) }}%
                                                    </span>
                                                @endif
                                            </td>
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

                    @if ($campProfit['unassignedFuel'] > 0 || $campProfit['maintenance'] > 0)
                        <div class="text-muted small mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            ต้นทุนที่ไม่ได้ปันเข้าแคมป์:
                            @if ($campProfit['unassignedFuel'] > 0)
                                ค่าน้ำมันที่ไม่ระบุแคมป์ {{ $money($campProfit['unassignedFuel']) }} บาท
                                (<a href="{{ route('fuel_records.index') }}">ไปเลือกแคมป์ในบันทึกน้ำมัน</a>)
                            @endif
                            @if ($campProfit['unassignedFuel'] > 0 && $campProfit['maintenance'] > 0) · @endif
                            @if ($campProfit['maintenance'] > 0)
                                ค่าซ่อมรถ {{ $money($campProfit['maintenance']) }} บาท
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="p-4 section-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                        <div>
                            <div class="section-title">ลูกหนี้ตามอายุหนี้</div>
                            <div class="section-note">นับจากวันครบกำหนดชำระ (วันที่ออก + เครดิตเทอม) · รวม VAT</div>
                        </div>
                        <span class="fw-semibold">{{ $money($aging['total']) }}</span>
                    </div>

                    @if ($aging['total'] <= 0)
                        <p class="text-muted mb-0">ไม่มียอดค้างรับ</p>
                    @else
                        @foreach ($aging['buckets'] as $b)
                            @php $pct = $aging['total'] > 0 ? $b['amount'] / $aging['total'] * 100 : 0; @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>{{ $b['label'] }} <span class="text-muted">({{ $b['count'] }} ใบ)</span></span>
                                    <span class="fw-semibold {{ $b['late'] && $b['amount'] > 0 ? 'text-danger' : '' }}">
                                        {{ $money($b['amount']) }}
                                    </span>
                                </div>
                                <div class="aging-bar">
                                    <div style="width: {{ round($pct, 1) }}%; background: {{ $b['late'] ? '#dc3545' : '#198754' }};"></div>
                                </div>
                            </div>
                        @endforeach

                        <div class="section-title small mt-4 mb-2">ลูกค้าที่ค้างมากที่สุด</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @foreach ($aging['topCustomers'] as $c)
                                        <tr>
                                            <td>
                                                {{ $c['name'] }}
                                                <div class="text-muted small">
                                                    {{ $c['invoices'] }} ใบ ·
                                                    @if ($c['oldest'] > 0)
                                                        <span class="text-danger">เลยกำหนดนานสุด {{ $c['oldest'] }} วัน</span>
                                                    @else
                                                        ยังไม่ถึงกำหนด
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="num fw-semibold">{{ $money($c['amount']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== ต้นทุนรายรถ + เงินล่วงหน้า + งานในมือ ===== --}}
        <div class="row g-3 mt-1">

            <div class="col-12 col-xl-6">
                <div class="p-4 section-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                        <div>
                            <div class="section-title">ต้นทุนรายรถ</div>
                            <div class="section-note">
                                ค่าน้ำมันต่อกม. สูงกว่าค่าเฉลี่ยกองรถเกิน 20% จะขึ้นสีแดง
                                @if ($truckCosts['avgPerKm'] !== null)
                                    (เฉลี่ย {{ $money($truckCosts['avgPerKm']) }} บาท/กม.)
                                @endif
                            </div>
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
                                        <th class="num">น้ำมัน</th>
                                        <th class="num">ซ่อม</th>
                                        <th class="num">บาท/กม.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($truckCosts['rows'] as $t)
                                        @php
                                            $high = $t['perKm'] !== null && $truckCosts['avgPerKm']
                                                && $t['perKm'] > $truckCosts['avgPerKm'] * 1.2;
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('trucks.show', $t['id_truck']) }}"
                                                   class="text-decoration-none text-dark fw-semibold">{{ $t['id_truck'] }}</a>
                                                <div class="text-muted small">{{ $t['brand'] }} · {{ $t['trips'] }} เที่ยว</div>
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
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="p-4 section-card h-100">
                    <div class="section-title">เงินรับล่วงหน้าคงเหลือ</div>
                    <div class="section-note mb-2">รับเงินแล้ว แต่ยังไม่ได้หักกับใบแจ้งหนี้ · ณ วันนี้</div>
                    <div class="kpi-number">{{ $money($advance['balance']) }}</div>
                    <div class="text-muted small mb-3">
                        รับมา {{ $money($advance['received']) }} · หักแล้ว {{ $money($advance['used']) }}
                    </div>

                    @forelse ($advance['rows'] as $a)
                        <div class="d-flex justify-content-between small py-1 border-top">
                            <span class="text-truncate me-2">
                                @if ($a['id_camp'])
                                    <a href="{{ route('camps.show', $a['id_camp']) }}" class="text-decoration-none text-dark">{{ $a['name'] }}</a>
                                @else
                                    {{ $a['name'] }}
                                @endif
                            </span>
                            <span class="fw-semibold text-nowrap">{{ $money($a['balance']) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">ไม่มีเงินล่วงหน้าค้างอยู่</p>
                    @endforelse
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="p-4 section-card h-100">
                    <div class="section-title">งานในมือ</div>
                    <div class="section-note mb-2">ตามใบสั่งขายที่ยืนยันแล้ว แต่ยังไม่ได้ส่ง · ก่อน VAT</div>
                    <div class="kpi-number">{{ $money($backlog['total']) }}</div>
                    <div class="text-muted small mb-3">{{ $backlog['camps'] }} แคมป์ที่ยังส่งไม่ครบ</div>

                    @forelse ($backlog['rows'] as $b)
                        <div class="py-2 border-top">
                            <div class="d-flex justify-content-between small">
                                <a href="{{ route('camps.show', $b['id_camp']) }}"
                                   class="text-decoration-none text-dark text-truncate me-2">{{ $b['name'] }}</a>
                                <span class="fw-semibold text-nowrap">{{ $money($b['remaining']) }}</span>
                            </div>
                            <div class="aging-bar mt-1" title="ส่งแล้ว {{ $b['percent'] }}%">
                                <div style="width: {{ $b['percent'] }}%; background: #1a1a1a;"></div>
                            </div>
                            <div class="text-muted" style="font-size:.75rem">ส่งแล้ว {{ $b['percent'] }}%</div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">ไม่มีงานค้างส่ง</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== ภาพรวมการปฏิบัติงาน (ณ วันนี้) ===== --}}
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
