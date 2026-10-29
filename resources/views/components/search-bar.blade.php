{{--
    แถบค้นหา — ช่องค้นหาช่องเดียวที่ค้นได้ทุกอย่างในหน้านั้น
    (+ ช่วงวันที่ + ตัวกรองสถานะ + ช่วงตัวเลข) + จำนวนต่อหน้า

    ใช้:
        <x-search-bar :action="route('customers.index')"
                      placeholder="ค้นหาชื่อ / เบอร์โทร / อีเมล"
                      :search="$search" :per-page="$perPage" />

    หน้าที่มีวันที่ — ส่ง :dates="true" พร้อมค่าช่วงวันที่ที่ controller อ่านจาก $this->dateRange()
        <x-search-bar ... :dates="true" :date-from="$dateFrom" :date-to="$dateTo"
                      date-label="วันที่ออก" />

    ตัวกรองสถานะ (dropdown) — ส่งรายการสถานะ ['value' => 'ป้ายไทย'] และค่าที่เลือกอยู่
        <x-search-bar ... :statuses="Camp::STATUS_LABELS" :status="$status" />

    ช่วงตัวเลข (ต่ำสุด–สูงสุด) — ส่งผลจาก $this->numberRanges() ใน controller
        <x-search-bar ... :ranges="$ranges" />

    controller ส่ง $search (จาก $this->searchTerm) และ $perPage (จาก $this->perPage)
--}}
@props(['action', 'placeholder' => 'พิมพ์คำที่ต้องการค้นหา', 'search' => '', 'perPage' => \App\Http\Controllers\Controller::DEFAULT_PER_PAGE, 'dates' => false, 'dateFrom' => null, 'dateTo' => null, 'dateLabel' => 'วันที่', 'statuses' => [], 'status' => null, 'statusName' => 'status', 'statusLabel' => 'สถานะ', 'ranges' => []])

@php
    // รองรับทั้ง ['draft' => 'ร่าง'] และ ['paid' => ['label' => 'ชำระครบแล้ว', ...]]
    $statusOptions = collect($statuses)->map(fn($l) => is_array($l) ? $l['label'] ?? '' : $l);

    // มีค่าค้นหา/ตัวกรองใดก็ได้ใน URL (นอกจากเลขหน้า จำนวนต่อหน้า และแท็บ) → แสดงปุ่มล้าง
    $hasFilter = collect(request()->except(['page', 'per_page', 'tab']))
        ->filter(fn($v) => $v !== null && $v !== '')
        ->isNotEmpty();
@endphp

<form method="GET" action="{{ $action }}" class="search-bar mb-3" role="search" data-no-validate>
    <div class="row g-2 align-items-end">

        {{-- ช่องค้นหาช่องเดียว + ปุ่มค้นหา --}}
        <div class="col-12 {{ $dates ? 'col-xl-5' : ($statusOptions->isNotEmpty() ? 'col-lg-5' : 'col-lg-5') }}">
            <label class="form-label small text-muted mb-1">ค้นหา</label>

            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" name="search" class="form-control" placeholder="{{ $placeholder }}"
                    value="{{ $search }}">
                <button type="submit" class="btn btn-search">
                    <i class="bi bi-search me-1"></i>ค้นหา
                </button>
                @if ($hasFilter)
                    <a href="{{ $action }}" class="btn btn-outline-secondary"
                        title="ล้างการค้นหาและตัวกรองทั้งหมด">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </div>

        {{-- ช่วงวันที่ (เฉพาะหน้าที่มีวันที่) --}}
        @if ($dates)
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small text-muted mb-1">{{ $dateLabel }} ตั้งแต่</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}"
                    onchange="this.form.submit()">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small text-muted mb-1">ถึง</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}"
                    onchange="this.form.submit()">
            </div>
        @endif

        {{-- ตัวกรองสถานะ --}}
        @if ($statusOptions->isNotEmpty())
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label small text-muted mb-1">{{ $statusLabel }}</label>
                <select name="{{ $statusName }}" class="form-select" onchange="this.form.submit()">
                    <option value="">— ทุก{{ $statusLabel }} —</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected((string) $status === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{ $slot }}

        {{-- จำนวนรายการต่อหน้า --}}
        <div class="col-12 col-md-auto ms-md-auto">
            @include('partials.per-page', ['perPage' => $perPage])
        </div>
    </div>

    {{-- ช่วงตัวเลข (ต่ำสุด–สูงสุด) --}}
    @if (!empty($ranges))
        <div class="row g-2 align-items-end mt-1 range-filters">
            @foreach ($ranges as $key => $r)
                <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
                    <label class="form-label small text-muted mb-1">
                        {{ $r['label'] }}@if ($r['unit'])
                            <span class="fw-normal">({{ $r['unit'] }})</span>
                        @endif
                    </label>
                    @if (!empty($r['options']))
                        {{-- แบบ dropdown: เลือกต่ำสุดแล้ว ช่องสูงสุดเหลือเฉพาะค่าที่ ≥ ต่ำสุด --}}
                        <div class="input-group" data-range-select>
                            <select name="{{ $key }}_min" class="form-select" data-range-min
                                aria-label="{{ $r['label'] }} ต่ำสุด">
                                <option value="">ต่ำสุด</option>
                                @foreach ($r['options'] as $opt)
                                    <option value="{{ $opt }}" @selected((string) $r['min'] === (string) $opt)>
                                        {{ $opt }}</option>
                                @endforeach
                            </select>
                            <span class="input-group-text">–</span>
                            <select name="{{ $key }}_max" class="form-select" data-range-max
                                aria-label="{{ $r['label'] }} สูงสุด">
                                <option value="">สูงสุด</option>
                                @foreach ($r['options'] as $opt)
                                    <option value="{{ $opt }}" @selected((string) $r['max'] === (string) $opt)>
                                        {{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="input-group">
                            <input type="number" name="{{ $key }}_min" class="form-control"
                                value="{{ $r['min'] }}" step="{{ $r['step'] }}" min="0"
                                placeholder="ต่ำสุด" aria-label="{{ $r['label'] }} ต่ำสุด">
                            <span class="input-group-text">–</span>
                            <input type="number" name="{{ $key }}_max" class="form-control"
                                value="{{ $r['max'] }}" step="{{ $r['step'] }}" min="0"
                                placeholder="สูงสุด" aria-label="{{ $r['label'] }} สูงสุด">
                        </div>
                    @endif
                </div>
            @endforeach
            <div class="col-12 col-sm-auto">
                <button type="submit" class="btn btn-outline-dark">
                    <i class="bi bi-funnel me-1"></i>กรองช่วง
                </button>
            </div>
        </div>
    @endif

    {{-- ค้นหาใหม่ทุกครั้ง กลับไปหน้า 1 --}}
    <input type="hidden" name="page" value="1">
</form>

{{-- dropdown ต่ำสุด–สูงสุด: ซ่อนตัวเลือกที่ทำให้ช่วงกลับด้าน --}}
@once
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-range-select]').forEach(function(group) {
                var minSel = group.querySelector('[data-range-min]');
                var maxSel = group.querySelector('[data-range-max]');

                function sync() {
                    var min = minSel.value === '' ? -Infinity : parseFloat(minSel.value);

                    // สูงสุด: เลือกได้เฉพาะค่าที่ ≥ ต่ำสุด
                    Array.prototype.forEach.call(maxSel.options, function(o) {
                        if (o.value === '') return;
                        var off = parseFloat(o.value) < min;
                        o.disabled = off;
                        o.hidden = off;
                    });
                }

                minSel.addEventListener('change', function() {
                    // ค่าสูงสุดเดิมน้อยกว่าต่ำสุดที่เพิ่งเลือก → ล้างทิ้ง ให้เลือกใหม่
                    if (maxSel.value !== '' && parseFloat(maxSel.value) < parseFloat(minSel
                        .value)) {
                        maxSel.value = '';
                    }
                    sync();
                });
                maxSel.addEventListener('change', sync);
                sync();
            });
        });
    </script>
@endonce
