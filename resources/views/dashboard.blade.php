@extends('layout')

@section('namepage')
    <div class="container">
        <h3 class="mb-0">แดชบอร์ด</h3>
    </div>
@endsection

@section('content')
    <style>
        /* ===============================
           DASHBOARD THEME (MATCH LAYOUT)
        =============================== */
        .dashboard-page {
            --card-border: rgba(0, 0, 0, .06);
            --card-shadow-lg: 0 10px 28px rgba(0, 0, 0, .06);
            --card-shadow-md: 0 8px 24px rgba(0, 0, 0, .05);
            --card-radius: 18px;
            --primary: #1a1a1a;
        }

        /* ===============================
           CARDS
        =============================== */
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

        /* ===============================
           KPI
        =============================== */
        .dashboard-page .kpi-number {
            font-size: 2.1rem;
            font-weight: 700;
            line-height: 1.1;
            color: var(--primary);
        }

        /* ===============================
           TABLE
        =============================== */
        .dashboard-page .table thead th {
            font-weight: 600;
            color: var(--primary);
        }

        .dashboard-page .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, .04);
        }

        /* ===============================
           BUTTONS
        =============================== */
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

        /* ===============================
           BADGE
        =============================== */
        .badge-soft {
            background: rgba(0, 0, 0, .05);
            color: #333;
        }
    </style>

    <div class="container py-3 dashboard-page">

        {{-- KPIs --}}
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="p-4 kpi-card h-100">
                    <div class="text-muted">รถบรรทุกรวม</div>
                    <div class="kpi-number mt-1">
                        {{ number_format($truckCounts['total']) }}
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span class="badge text-bg-dark">
                            พร้อมใช้งาน {{ $truckCounts['active'] }}
                        </span>
                        <span class="badge text-bg-secondary">
                            ซ่อมบำรุง {{ $truckCounts['maintenance'] }}
                        </span>
                        <span class="badge text-bg-light text-dark">
                            ปลดประจำการ {{ $truckCounts['retired'] }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="p-4 kpi-card h-100">
                    <div class="text-muted">พนักงานขับรถ</div>
                    <div class="kpi-number mt-1">
                        {{ number_format($driversTotal) }}
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary btn-sm">
                            ดูทั้งหมด
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection