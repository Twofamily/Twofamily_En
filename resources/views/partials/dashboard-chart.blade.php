{{--
    สรุปรายได้–ต้นทุน + กราฟแท่ง 2 ตัว แยกตามสายรายได้ของบริษัท — ขายสินค้า / บริการแคมป์
    วางไว้ล่างสุดของหน้า ควบคุมด้วยตัวกรองช่วงเวลาที่อยู่ใต้กราฟ

    ใช้กราฟแท่ง (ไม่ใช่เส้นโค้ง) เพราะแต่ละจุดคือ "ยอดรวมทั้งเดือน"
    เส้นโค้งจะลากผ่านเดือนที่ไม่มีข้อมูลจนดูเหมือนมียอด ทำให้อ่านผิด
--}}

@php
    $f      = $revenue['filter'];
    $series = $revenue['series'];
    $noCost = $series['totalCost'] == 0;
    $profit = $series['totalRevenue'] - $series['totalCost'];

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

    // ยอดรวมของแต่ละสาย ใช้ทั้งแสดงหัวการ์ด และตัดสินว่าสายนี้มีข้อมูลให้วาดกราฟไหม
    foreach ($charts as &$c) {
        $c['revenueSum'] = array_sum($c['data']['revenue']);
        $c['costSum']    = array_sum($c['data']['cost']);
        $c['hasData']    = $c['revenueSum'] > 0 || $c['costSum'] > 0;
    }
    unset($c);

    // ส่งเฉพาะสายที่มีข้อมูล และเฉพาะสองชุดที่กราฟใช้จริง
    $chartData = collect($charts)->where('hasData', true)->map(fn ($c) => [
        'id'      => $c['id'],
        'revenue' => $c['data']['revenue'],
        'cost'    => $c['data']['cost'],
    ])->values();
@endphp

<span id="charts"></span>

@if (!$revenue['hasData'])
    <div class="p-4 section-card text-center text-muted">
        ไม่มีข้อมูลในช่วง {{ $f['label'] }} — ลองเลือกช่วงเวลาอื่น
    </div>
@else
    {{-- ===== สรุปยอดของช่วงที่เลือก ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="p-3 section-card h-100">
                <div class="text-muted small">รายได้รวม (ก่อน VAT)</div>
                <div class="fs-4 fw-semibold text-success">{{ number_format($series['totalRevenue'], 2) }}</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 section-card h-100">
                <div class="text-muted small">ต้นทุนรถ (น้ำมัน + ซ่อม)</div>
                <div class="fs-4 fw-semibold">{{ number_format($series['totalCost'], 2) }}</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 section-card h-100">
                <div class="text-muted small">รายได้หลังหักต้นทุนรถ</div>
                <div class="fs-4 fw-semibold {{ $profit < 0 ? 'text-danger' : '' }}">{{ number_format($profit, 2) }}</div>
                <div class="text-muted small">ยังไม่รวมราคาทุนสินค้า</div>
            </div>
        </div>
    </div>

    @foreach ($charts as $c)
        <div class="p-4 section-card {{ !$loop->first ? 'mt-3' : '' }}">
            <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                <div>
                    <h6 class="mb-1">{{ $c['title'] }}</h6>
                    <div class="text-muted small">{{ $c['note'] }}</div>
                </div>
                <div class="text-end">
                    <div class="fw-semibold text-success">{{ number_format($c['revenueSum'], 2) }} บาท</div>
                    <span class="badge badge-soft">{{ $f['bucketLabel'] }}</span>
                </div>
            </div>

            @if ($c['hasData'])
                <div class="chart-box"><canvas id="{{ $c['id'] }}"></canvas></div>

                @if ($noCost)
                    <div class="text-muted small mt-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        แท่งต้นทุนยังเป็นศูนย์ เพราะยังไม่มีการบันทึกค่าน้ำมันและค่าซ่อมรถในช่วงนี้
                    </div>
                @endif
            @else
                {{-- ไม่วาดกราฟเปล่า (แกน 0–1 ทำให้ดูเหมือนระบบพัง) แสดงคำอธิบายแทน --}}
                <div class="text-center text-muted py-4">
                    <i class="bi bi-bar-chart fs-2 d-block mb-2"></i>
                    ยังไม่มีรายได้จาก{{ $c['title'] }}ในช่วง {{ $f['label'] }}
                </div>
            @endif
        </div>
    @endforeach

    {{-- โหลด Chart.js จากในโปรเจกต์ ไม่พึ่ง CDN — นำเสนอแบบไม่มีเน็ตก็ยังแสดงกราฟได้ --}}
    <script src="{{ asset('js/vendor/chart.umd.min.js') }}"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;
            const labels = @json($series['labels']);
            const charts = @json($chartData);
            const baht = (v) => new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(v);
            // ดึงฟอนต์และขนาดจาก body จริง ๆ แทนที่จะฮาร์ดโค้ด
            // ถ้าไม่ตั้งให้ตรง ตัวหนังสือในกราฟจะเล็กกว่าที่อื่นทั้งหน้า
            const pageStyle = getComputedStyle(document.body);
            Chart.defaults.font.family = pageStyle.fontFamily;
            Chart.defaults.font.size = parseFloat(pageStyle.fontSize) || 16;
            Chart.defaults.color = '#525252';
            const bar = (label, data, color) => ({
                label,
                data,
                backgroundColor: color,
                borderRadius: 4,
                maxBarThickness: 36,
            });
            charts.forEach(({ id, revenue, cost }) => {
                const el = document.getElementById(id);
                if (!el) return;
                new Chart(el, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            bar('รายได้', revenue, '#198754'),
                            bar('ต้นทุน', cost, '#adb5bd'),
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
                                ticks: { callback: (v) => baht(v), precision: 0 },
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
