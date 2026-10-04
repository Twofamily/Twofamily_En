{{--
    กราฟเส้นเทียบรายได้กับต้นทุน — ภาพรวมบริษัท
    วางไว้ล่างสุดของหน้า ควบคุมด้วยตัวกรองช่วงเวลาด้านบน
--}}

@php
    $f        = $revenue['filter'];
    $series   = $revenue['series'];
    $noCost   = array_sum($series['cost']) == 0;
@endphp

<div class="p-4 section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">รายได้ และ ต้นทุน</h6>
        <span class="badge badge-soft">{{ $f['bucketLabel'] }}</span>
    </div>

    @if (!$revenue['hasData'])
        <p class="text-muted text-center my-5">
            ไม่มีข้อมูลในช่วง {{ $f['label'] }} — ลองเลือกช่วงเวลาอื่น
        </p>
    @else
        <div class="chart-box"><canvas id="chartOverview"></canvas></div>

        @if ($noCost)
            <div class="alert alert-warning mt-3 mb-0 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>เส้นต้นทุนยังเป็นศูนย์</strong> เพราะยังไม่มีการบันทึกค่าน้ำมันและค่าซ่อมรถในช่วงนี้
                เมื่อเริ่มกรอกข้อมูลที่เมนู "ต้นทุนค่าน้ำมัน" และ "ซ่อมบำรุงรถ" เส้นนี้จะขึ้นเอง
            </div>
        @endif

        <div class="text-muted small mt-3">
            <i class="bi bi-info-circle me-1"></i>
            รายได้ใช้มูลค่างานที่ส่ง (ก่อน VAT) เพื่อให้เทียบกับต้นทุนได้ตรง ·
            ต้นทุนนี้<strong>ยังไม่รวมราคาทุนสินค้า</strong> เพราะฐานข้อมูลยังไม่มีคอลัมน์ราคาทุน
            จึงยังไม่ใช่กำไรที่แท้จริง (ดู DASHBOARD.md)
        </div>
    @endif
</div>

@if ($revenue['hasData'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;

            const el = document.getElementById('chartOverview');
            if (!el) return;

            const series = @json($series);
            const baht = (v) => new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(v);

            Chart.defaults.font.family = "'Noto Sans Thai', system-ui, -apple-system, sans-serif";
            Chart.defaults.color = '#525252';

            new Chart(el, {
                type: 'line',
                data: {
                    labels: series.labels,
                    datasets: [
                        {
                            label: 'รายได้',
                            data: series.delivered,
                            borderColor: '#1a1a1a',
                            backgroundColor: 'rgba(26,26,26,.08)',
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            tension: .3,
                            fill: true,
                        },
                        {
                            label: 'ต้นทุน',
                            data: series.cost,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220,53,69,.08)',
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            tension: .3,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: (c) => ` ${c.dataset.label}: ${baht(c.parsed.y)} บาท`,
                                // แยกค่าน้ำมันกับค่าซ่อมให้ดูตอน hover เส้นต้นทุน
                                afterLabel: (c) => c.dataset.label === 'ต้นทุน'
                                    ? `   น้ำมัน ${baht(series.fuel[c.dataIndex])} · ซ่อม ${baht(series.maintenance[c.dataIndex])}`
                                    : undefined,
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (v) => baht(v) },
                            grid: { color: 'rgba(0,0,0,.06)' },
                        },
                        x: { grid: { display: false } },
                    },
                },
            });
        })();
    </script>
@endif
