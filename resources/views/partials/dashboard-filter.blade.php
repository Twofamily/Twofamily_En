{{--
    ตัวกรองช่วงเวลาของกราฟ — วางไว้ใต้กราฟ
    ข้อมูลมาจาก $revenue['filter'] (App\Http\Controllers\Dashboard\RevenueReport)
--}}

@php
    $f = $revenue['filter'];
@endphp

{{-- ===== ตัวกรองช่วงเวลา ===== --}}
<form method="GET" action="{{ route('dashboard') }}" class="p-3 p-md-4 section-card mt-3" id="revenueFilter">
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
