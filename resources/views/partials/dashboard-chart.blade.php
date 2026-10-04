{{--
    กราฟเส้น 2 ตัว แยกตามสายรายได้ของบริษัท — ขายสินค้า / บริการแคมป์
    วางไว้ล่างสุดของหน้า ควบคุมด้วยตัวกรองช่วงเวลาที่อยู่ใต้กราฟ
--}}

@php
    $f      = $revenue['filter'];
    $series = $revenue['series'];
    $noCost = $series['totalCost'] == 0;

    $charts = [
        [
            'id'    => 'chartProduct',
            'title' => 'ขายสินค้า',
            'note'  => 'ใบส่งของที่ไม่ได้ผูกกับแคมป์ (ส่งตรงให้ลูกค้า)',
            'data'  => $series['product'],
        ],
        [
            'id'    => 'chartCamp',
            'title' => 'บริการแคมป์',
            'note'  => 'ใบส่งของที่ผูกกับแคมป์งาน',
            'data'  => $series['camp'],
        ],
    ];

    // ส่งเฉพาะสองชุดที่กราฟใช้จริง ไม่ต้องยัด fuel/maintenance ลงหน้าเว็บ
    $chartData = array_map(fn ($c) => [
        'id'      => $c['id'],
        'revenue' => $c['data']['revenue'],
        'cost'    => $c['data']['cost'],
    ], $charts);
@endphp

<span id="charts"></span>

@if (!$revenue['hasData'])
    <div class="p-4 section-card text-center text-muted">
        ไม่มีข้อมูลในช่วง {{ $f['label'] }} — ลองเลือกช่วงเวลาอื่น
    </div>
@else
    @foreach ($charts as $c)
        <div class="p-4 section-card {{ !$loop->first ? 'mt-3' : '' }}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="mb-1">{{ $c['title'] }}</h6>
                    <div class="text-muted small">{{ $c['note'] }}</div>
                </div>
                <span class="badge badge-soft">{{ $f['bucketLabel'] }}</span>
            </div>

            <div class="chart-box"><canvas id="{{ $c['id'] }}"></canvas></div>

            @if ($noCost)
                <div class="text-muted small mt-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    เส้นต้นทุนยังเป็นศูนย์ เพราะยังไม่มีการบันทึกค่าน้ำมันและค่าซ่อมรถในช่วงนี้
                </div>
            @endif
        </div>
    @endforeach

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;

            const labels = @json($series['labels']);
            const charts = @json($chartData);

            const baht = (v) => new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(v);

            // ดึงฟอนต์และขนาดจาก body จริง ๆ แทนที่จะฮาร์ดโค้ด
            // แอปไม่ได้ประกาศ font-family เอง (ใช้ของ Bootstrap) ส่วน Chart.js มีค่าเริ่มต้น 12px
            // ถ้าไม่ตั้งให้ตรง ตัวหนังสือในกราฟจะเล็กกว่าที่อื่นทั้งหน้า
            const pageStyle = getComputedStyle(document.body);
            Chart.defaults.font.family = pageStyle.fontFamily;
            Chart.defaults.font.size = parseFloat(pageStyle.fontSize) || 16;
            Chart.defaults.color = '#525252';

            const line = (label, data, color, fill) => ({
                label,
                data,
                borderColor: color,
                backgroundColor: fill,
                borderWidth: 2.5,
                pointRadius: 3,
                pointHoverRadius: 6,
                tension: .3,
                fill: true,
            });

            charts.forEach(({ id, revenue, cost }) => {
                const el = document.getElementById(id);
                if (!el) return;

                new Chart(el, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            line('รายได้', revenue, '#198754', 'rgba(25,135,84,.10)'),
                            line('ต้นทุน', cost, '#6c757d', 'rgba(108,117,125,.10)'),
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
            });
        })();
    </script>
@endif
