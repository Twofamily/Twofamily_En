{{--
    ส่วนรายได้ของแดชบอร์ด — ข้อมูลมาจาก $revenue (App\Http\Controllers\Dashboard\RevenueReport)

    ยังไม่มีกราฟกำไร เพราะฐานข้อมูลยังไม่มีราคาทุนสินค้า
    และ fuel_records / truck_maintenances ยังไม่มีข้อมูล — รายละเอียดอยู่ใน DASHBOARD.md
--}}

@php
    $f   = $revenue['filter'];
    $kpi = $revenue['kpi'];
@endphp

<div class="revenue-section">

    {{-- ===== ตัวกรองช่วงเวลา ===== --}}
    <form method="GET" action="{{ route('dashboard') }}" class="p-3 p-md-4 section-card mb-3" id="revenueFilter">
        <div class="d-flex flex-wrap align-items-center gap-3">

            <div class="d-flex flex-wrap gap-2" role="group" aria-label="ช่วงเวลา">
                @foreach ($f['presets'] as $key => $label)
                    <button type="submit" name="preset" value="{{ $key }}"
                            class="btn btn-sm {{ $f['preset'] === $key ? 'btn-dark' : 'btn-outline-secondary' }}"
                            @if ($key === 'custom') onclick="document.getElementById('customDates').classList.remove('d-none')" @endif>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <label for="bucket" class="form-label mb-0 small text-muted">มุมมอง</label>
                <select name="bucket" id="bucket" class="form-select form-select-sm" style="width:auto"
                        onchange="document.getElementById('revenueFilter').submit()">
                    @foreach ($f['buckets'] as $key => $label)
                        <option value="{{ $key }}" @selected($f['bucketChoice'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ช่องวันที่ เปิดไว้เมื่อเลือกกำหนดเอง --}}
        <div id="customDates" class="row g-2 align-items-end mt-2 {{ $f['preset'] === 'custom' ? '' : 'd-none' }}">
            <div class="col-6 col-md-auto">
                <label for="from" class="form-label small text-muted mb-1">ตั้งแต่</label>
                <input type="date" id="from" name="from" value="{{ $f['from'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-auto">
                <label for="to" class="form-label small text-muted mb-1">ถึง</label>
                <input type="date" id="to" name="to" value="{{ $f['to'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button type="submit" name="preset" value="custom" class="btn btn-dark btn-sm">ดูข้อมูล</button>
            </div>
        </div>

        <div class="text-muted small mt-2">แสดงข้อมูล {{ $f['label'] }}</div>
    </form>

    {{-- ===== KPI รายได้ ===== --}}
    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="p-4 kpi-card h-100">
                <div class="text-muted kpi-label">มูลค่างานที่ส่ง</div>
                <div class="kpi-number mt-1">{{ number_format($kpi['delivered'], 2) }}</div>
                <div class="text-muted small mt-2">บาท · ก่อน VAT · จากใบส่งของ</div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="p-4 kpi-card h-100">
                <div class="text-muted kpi-label">ออกบิลแล้ว</div>
                <div class="kpi-number mt-1">{{ number_format($kpi['invoiced'], 2) }}</div>
                <div class="text-muted small mt-2">บาท · รวม VAT · จากใบแจ้งหนี้</div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="p-4 kpi-card h-100">
                <div class="text-muted kpi-label">เงินเข้าจริง</div>
                <div class="kpi-number mt-1">{{ number_format($kpi['paid'], 2) }}</div>
                <div class="text-muted small mt-2">บาท · จากการรับชำระ</div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="p-4 kpi-card h-100">
                <div class="text-muted kpi-label">ค้างรับ</div>
                <div class="kpi-number mt-1 {{ $kpi['outstanding'] > 0 ? 'text-danger' : '' }}">
                    {{ number_format($kpi['outstanding'], 2) }}
                </div>
                <div class="text-muted small mt-2">บาท · ออกบิลแล้วแต่ยังไม่ได้รับเงิน</div>
            </div>
        </div>
    </div>

    {{-- ===== กราฟ ===== --}}
    @if (!$revenue['hasData'])
        <div class="p-4 section-card mt-3 text-center text-muted">
            ไม่มีข้อมูลในช่วง {{ $f['label'] }} — ลองเลือกช่วงเวลาอื่น
        </div>
    @else
        <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="p-4 section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">รายได้ตามช่วงเวลา</h6>
                        <span class="badge badge-soft">{{ $f['bucketLabel'] }}</span>
                    </div>
                    <div class="chart-box"><canvas id="chartTimeline"></canvas></div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="p-4 section-card h-100">
                    <h6 class="mb-3">รายได้แยกตามแคมป์</h6>
                    @if (count($revenue['byCamp']['labels']) === 0)
                        <p class="text-muted mb-0">ยังไม่มีใบส่งของที่ผูกกับแคมป์ในช่วงนี้</p>
                    @else
                        <div class="chart-box"><canvas id="chartCamp"></canvas></div>
                    @endif
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="p-4 section-card h-100">
                    <h6 class="mb-3">รายได้แยกตามสินค้า</h6>
                    @if (count($revenue['byProduct']['labels']) === 0)
                        <p class="text-muted mb-0">ยังไม่มีข้อมูลสินค้าในช่วงนี้</p>
                    @else
                        <div class="chart-box"><canvas id="chartProduct"></canvas></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- หมายเหตุความถูกต้องของตัวเลข ไม่ใช่ของตกแต่ง --}}
        <div class="p-3 section-card mt-3 small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            <strong>ยังไม่มีกราฟกำไร</strong> เพราะฐานข้อมูลยังไม่มีราคาทุนสินค้า
            และยังไม่มีข้อมูลค่าน้ำมัน/ค่าซ่อมรถ ·
            ตัวเลข "ออกบิลแล้ว" อิงวันที่บันทึกใบแจ้งหนี้ เพราะตาราง invoices ไม่มีคอลัมน์วันที่ออกบิล
        </div>
    @endif
</div>

@if ($revenue['hasData'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;

            const baht = (v) => new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(v);

            Chart.defaults.font.family = "'Noto Sans Thai', system-ui, -apple-system, sans-serif";
            Chart.defaults.color = '#525252';

            const money = {
                ticks: { callback: (v) => baht(v) },
                grid: { color: 'rgba(0,0,0,.06)' },
                beginAtZero: true,
            };

            const tooltipBaht = {
                callbacks: {
                    label: (c) => ` ${c.dataset.label ?? c.label}: ${baht(c.parsed.y ?? c.parsed.x ?? c.parsed)} บาท`,
                },
            };

            // ---------- รายได้ตามช่วงเวลา ----------
            const series = @json($revenue['series']);

            new Chart(document.getElementById('chartTimeline'), {
                type: 'bar',
                data: {
                    labels: series.labels,
                    datasets: [
                        { label: 'มูลค่างานที่ส่ง', data: series.delivered, backgroundColor: '#1a1a1a', borderRadius: 4 },
                        { label: 'ออกบิล',          data: series.invoiced,  backgroundColor: '#9ca3af', borderRadius: 4 },
                        { label: 'เงินเข้าจริง',     data: series.paid,      backgroundColor: '#d4d4d4', borderRadius: 4 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom' }, tooltip: tooltipBaht },
                    scales: { y: money, x: { grid: { display: false } } },
                },
            });

            // ---------- แยกตามแคมป์ ----------
            const camp = @json($revenue['byCamp']);
            const campEl = document.getElementById('chartCamp');

            if (campEl && camp.labels.length) {
                // ชื่อแคมป์ซ้ำกันได้ (คนละงานแต่ชื่อเดียวกัน) จึงต่อรหัสแคมป์ไว้ให้แยกออก
                const campLabels = camp.labels.map((n, i) => camp.codes[i] ? `${n} (${camp.codes[i]})` : n);

                new Chart(campEl, {
                    type: 'bar',
                    data: {
                        labels: campLabels,
                        datasets: [{ label: 'รายได้', data: camp.amounts, backgroundColor: '#1a1a1a', borderRadius: 4 }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: tooltipBaht },
                        scales: { x: money, y: { grid: { display: false } } },
                    },
                });
            }

            // ---------- แยกตามสินค้า ----------
            const product = @json($revenue['byProduct']);
            const productEl = document.getElementById('chartProduct');

            if (productEl && product.labels.length) {
                new Chart(productEl, {
                    type: 'doughnut',
                    data: {
                        labels: product.labels,
                        datasets: [{
                            data: product.amounts,
                            backgroundColor: ['#1a1a1a', '#525252', '#9ca3af', '#d4d4d4', '#e5e5e5', '#404040'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '58%',
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { label: (c) => ` ${c.label}: ${baht(c.parsed)} บาท` } },
                        },
                    },
                });
            }
        })();
    </script>
@endif
