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
@endphp

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

            <div class="row g-3 mt-2 text-muted small">
                <div class="col-auto">รายได้รวม <strong class="text-dark">{{ number_format(array_sum($c['data']['revenue']), 2) }}</strong> บาท</div>
                <div class="col-auto">ต้นทุน (ปันส่วน) <strong class="text-dark">{{ number_format(array_sum($c['data']['cost']), 2) }}</strong> บาท</div>
            </div>
        </div>
    @endforeach

    {{-- หมายเหตุความถูกต้องของตัวเลข ไม่ใช่ของตกแต่ง --}}
    <div class="p-3 section-card mt-3 small text-muted">
        @if ($noCost)
            <div class="alert alert-warning mb-3 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>เส้นต้นทุนยังเป็นศูนย์</strong> เพราะยังไม่มีการบันทึกค่าน้ำมันและค่าซ่อมรถในช่วงนี้
            </div>
        @endif

        <i class="bi bi-info-circle me-1"></i>
        <strong>ต้นทุนในกราฟเป็นตัวเลขปันส่วน ไม่ใช่ต้นทุนที่วัดจริงของแต่ละสาย</strong> —
        ค่าน้ำมันและค่าซ่อมบันทึกไว้ที่ "รถ" ไม่ได้ผูกกับใบส่งของหรือแคมป์
        จึงแบ่งเข้าสองสายตามสัดส่วนรายได้ของแต่ละเดือน ·
        และ<strong>ยังไม่รวมราคาทุนสินค้า</strong> เพราะฐานข้อมูลไม่มีคอลัมน์ราคาทุน
        ส่วนต่างระหว่างสองเส้นจึง<strong>ยังไม่ใช่กำไร</strong> (ดู DASHBOARD.md)
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;

            const labels = @json($series['labels']);
            const charts = @json(array_map(fn ($c) => ['id' => $c['id'], 'data' => $c['data']], $charts));

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

            charts.forEach(({ id, data }) => {
                const el = document.getElementById(id);
                if (!el) return;

                new Chart(el, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            line('รายได้', data.revenue, '#1a1a1a', 'rgba(26,26,26,.08)'),
                            line('ต้นทุน', data.cost, '#dc3545', 'rgba(220,53,69,.08)'),
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
                                        ? `   น้ำมัน ${baht(data.fuel[c.dataIndex])} · ซ่อม ${baht(data.maintenance[c.dataIndex])}`
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
            });
        })();
    </script>
@endif
