@php
    $f      = $revenue['filter'];
    $series = $revenue['series'];
@endphp

<div class="p-4 section-card">
    <div class="d-flex justify-content-between align-items-start mb-3 gap-2 flex-wrap">
        <div>
            <div class="section-title">รายได้ ต้นทุน และกำไร</div>
        </div>
        <span class="badge badge-soft">{{ $f['bucketLabel'] }} · {{ $f['label'] }}</span>
    </div>

    @if (! $revenue['hasData'])
        <div class="text-center text-muted py-5">
            <i class="bi bi-bar-chart fs-2 d-block mb-2"></i>
            ไม่มีรายได้หรือต้นทุนในช่วง {{ $f['label'] }} — ลองเลือกช่วงเวลาอื่น
        </div>
    @else
        <div class="chart-box"><canvas id="financeChart"></canvas></div>

        @if ($series['totalCost'] == 0)
            <div class="text-muted small mt-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                ต้นทุนยังเป็นศูนย์ เพราะยังไม่มีการบันทึกค่าน้ำมันหรือค่าซ่อมรถในช่วงนี้
            </div>
        @endif

        <script src="{{ asset('js/vendor/chart.umd.min.js') }}"></script>
        <script>
            (function () {
                const el = document.getElementById('financeChart');
                if (!el || typeof Chart === 'undefined') return;

                const labels = @json($series['labels']);
                const product = @json($series['product']['revenue']);
                const camp = @json($series['camp']['revenue']);
                const cost = @json($series['cost']);
                const profit = @json($series['profit']);

                const baht = (v) => new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(v);

                const pageStyle = getComputedStyle(document.body);
                Chart.defaults.font.family = pageStyle.fontFamily;
                Chart.defaults.font.size = parseFloat(pageStyle.fontSize) || 16;
                Chart.defaults.color = '#525252';

                const bar = (label, data, color, stack) => ({
                    type: 'bar',
                    label,
                    data,
                    stack,
                    backgroundColor: color,
                    borderRadius: 4,
                    maxBarThickness: 34,
                    order: 2,
                });

                new Chart(el, {
                    data: {
                        labels,
                        datasets: [
                            bar('รายได้ · ขายสินค้า', product, '#7cc49a', 'revenue'),
                            bar('รายได้ · บริการแคมป์', camp, '#198754', 'revenue'),
                            bar('ต้นทุน', cost, '#adb5bd', 'cost'),
                            {
                                type: 'line',
                                label: 'กำไรขั้นต้น',
                                data: profit,
                                stack: 'profit',
                                borderColor: '#1a1a1a',
                                backgroundColor: '#1a1a1a',
                                borderWidth: 2,
                                tension: 0,
                                pointRadius: 3,
                                pointBackgroundColor: profit.map((v) => (v < 0 ? '#dc3545' : '#1a1a1a')),
                                pointBorderColor: profit.map((v) => (v < 0 ? '#dc3545' : '#1a1a1a')),
                                order: 1,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { sort: (a, b) => a.datasetIndex - b.datasetIndex },
                            },
                            tooltip: {
                                callbacks: {
                                    label: (c) => ` ${c.dataset.label}: ${baht(c.parsed.y)} บาท`,
                                    footer: (items) => {
                                        const i = items[0].dataIndex;
                                        const rev = product[i] + camp[i];
                                        if (rev <= 0) return '';
                                        return `อัตรากำไร ${(profit[i] / rev * 100).toFixed(1)}%`;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: { stacked: true, grid: { display: false } },
                            y: {
                                stacked: true,
                                ticks: { callback: (v) => baht(v), precision: 0 },
                                grid: { color: 'rgba(0,0,0,.06)' },
                            },
                        },
                    },
                });
            })();
        </script>
    @endif
</div>
