@extends('layout')

@section('namepage')
    <div class="container">
        <h3>{{ $camp->name_camp }}</h3>
    </div>
@endsection

@section('content')
    <div class="container py-2">
        @if (session('info'))
            <div class="alert alert-info shadow-sm">{{ session('info') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning shadow-sm">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <a href="{{ route('camps.index') }}" class="btn btn-sm btn-light mb-3">
            <i class="bi bi-arrow-left me-1"></i>
            กลับหน้ารายการ
        </a>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <div class="text-muted small mb-1">{{ $camp->code_camp }}</div>
                        <h5 class="mb-0">{{ $camp->name_camp }}</h5>
                    </div>

                    <div class="text-end">
                        <span class="badge bg-{{ $camp->status_camp === 'active' ? 'success' : 'secondary' }} fs-6 mb-2">
                            {{ $camp->status_label }}
                        </span>
                        <br>
                        <a href="{{ route('camps.edit', $camp->id_camp) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil-square me-1"></i>
                            แก้ไขข้อมูล
                        </a>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">ลูกค้า</div>
                        <div>{{ $camp->customer->name_customer ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">อ้างอิงใบเสนอราคา</div>
                        <div>
                            @if ($camp->quotation)
                                <a href="{{ route('quotations.show', $camp->quotation) }}" class="text-decoration-none">
                                    {{ $camp->quotation->code_quot }}
                                </a>
                                <span class="text-muted">
                                    · {{ number_format($camp->quotation->total_amount, 2) }} บาท
                                </span>
                            @else
                                <span class="text-muted">ยังไม่ได้อ้างอิงใบเสนอราคา</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">ที่ตั้ง</div>
                        <div>{{ $camp->full_address ?: 'ยังไม่ระบุที่อยู่' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">ผู้ติดต่อหน้างาน</div>
                        <div>
                            {{ $camp->contact_name ?: '-' }}
                            @if ($camp->contact_phone)
                                <span class="text-muted">· {{ $camp->contact_phone }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">พิกัด</div>
                        <div>
                            @if ($camp->latitude && $camp->longitude)
                                {{ $camp->latitude }}, {{ $camp->longitude }}
                            @else
                                <span class="text-danger">ยังไม่ปักหมุด (คำนวณระยะทางอัตโนมัติไม่ได้)</span>
                            @endif
                        </div>
                    </div>

                    @if ($camp->note)
                        <div class="col-12">
                            <div class="text-muted small">หมายเหตุ</div>
                            <div>{{ $camp->note }}</div>
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="text-muted small">
                            สร้างเมื่อ {{ $camp->created_at->format('d/m/Y') }}
                            @if ($camp->updated_at->ne($camp->created_at))
                                · แก้ไขล่าสุด {{ $camp->updated_at->format('d/m/Y') }}
                            @endif
                        </div>
                    </div>
                </div>

                @if ($camp->latitude && $camp->longitude)
                    <div id="campShowMap" class="mt-3"
                        style="width:100%;height:320px;border-radius:10px;background:#e9ecef;"></div>

                    <div id="campMapError" class="alert alert-danger mt-2 d-none" role="alert"></div>

                    <div class="mt-2">
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $camp->latitude }},{{ $camp->longitude }}"
                            target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                            เปิดใน Google Maps
                        </a>
                    </div>
                @endif

            </div>
        </div>

        @if ($camp->status_camp === 'active')
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="mb-3">เพิ่มรถบรรทุกเข้าแคมป์</h6>

                    @if ($availableTrucks->isEmpty())
                        <div class="text-muted small">
                            ไม่มีรถที่พร้อมใช้งานเหลืออยู่ (รถอาจอยู่ระหว่างซ่อมบำรุง หรือติดงานที่แคมป์อื่น)
                        </div>
                    @else
                        <form method="POST" action="{{ route('camps.trucks.assign', $camp->id_camp) }}"
                            class="row g-2 align-items-end">
                            @csrf

                            <div class="col-md-4">
                                <label class="form-label small mb-1">เลือกรถ</label>
                                <select name="id_truck" class="form-select" required>
                                    <option value="">— เลือกรถ —</option>
                                    @foreach ($availableTrucks as $truck)
                                        <option value="{{ $truck->id_truck }}">
                                            {{ $truck->id_truck }}
                                            ({{ $truck->brand->name_brand ?? '' }} {{ $truck->model->name_model ?? '' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small mb-1">วันที่เริ่มทำงาน</label>
                                <input type="date" name="assigned_date" class="form-control" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small mb-1">หมายเหตุ</label>
                                <input type="text" name="note" class="form-control" placeholder="ไม่บังคับ">
                            </div>

                            <div class="col-md-2">
                                <button class="btn btn-dark w-100">+ เพิ่มรถ</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- ความคืบหน้าการส่งของ --}}
        @if ($progress->isNotEmpty())
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">
                    ความคืบหน้าการส่งของ (เทียบกับใบเสนอราคา)
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>รายการ</th>
                                <th class="text-end">ตามสัญญา</th>
                                <th class="text-end">ส่งแล้ว</th>
                                <th class="text-end">คงเหลือ</th>
                                <th style="width: 200px;">ความคืบหน้า</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($progress as $row)
                                <tr>
                                    <td>{{ $row['name_product'] }}</td>
                                    <td class="text-end">{{ number_format($row['ordered'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['delivered'], 2) }}</td>
                                    <td class="text-end fw-semibold">
                                        @if ($row['over'] > 0)
                                            <span class="text-danger">
                                                เกิน {{ number_format($row['over'], 2) }}
                                            </span>
                                        @else
                                            {{ number_format($row['remaining'], 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 18px;">
                                            <div class="progress-bar bg-{{ $row['over'] > 0 ? 'danger' : ($row['percent'] >= 100 ? 'success' : 'dark') }}"
                                                role="progressbar" style="width: {{ $row['percent'] }}%;">
                                                {{ $row['percent'] }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ใบส่งของของแคมป์นี้ --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">ใบส่งของของแคมป์นี้</span>

                @if ($camp->status_camp === 'active')
                    <a href="{{ route('delivery-notes.create', ['camp' => $camp->id_camp]) }}"
                        class="btn btn-sm btn-dark">+ สร้างใบส่งของ</a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>เลขที่</th>
                            <th>วันที่ส่ง</th>
                            <th class="text-end">มูลค่า</th>
                            <th class="text-center">ใบแจ้งหนี้</th>
                            <th class="text-center" style="width: 100px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deliveryNotes as $note)
                            <tr>
                                <td class="fw-semibold">{{ $note->code_delivery }}</td>
                                <td>{{ $note->delivery_date->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    {{ number_format($note->details->sum('total_price'), 2) }}
                                </td>
                                <td class="text-center">
                                    @if ($note->invoice)
                                        <span class="badge bg-success">ออกแล้ว</span>
                                    @else
                                        <span class="badge bg-secondary">รอออก</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('delivery-notes.show', $note) }}"
                                        class="btn btn-sm btn-outline-primary">ดู</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    ยังไม่มีใบส่งของจากแคมป์นี้
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                รถบรรทุกที่ทำงานที่แคมป์นี้
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ทะเบียน</th>
                            <th>ยี่ห้อ / รุ่น</th>
                            <th>วันที่เริ่ม</th>
                            <th>หมายเหตุ</th>
                            <th>สถานะ</th>
                            <th class="text-center" style="width: 260px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($camp->trucks as $truck)
                            <tr>
                                <td class="fw-semibold">{{ $truck->id_truck }}</td>
                                <td>{{ $truck->brand->name_brand ?? '-' }} {{ $truck->model->name_model ?? '' }}</td>
                                <td>{{ \Carbon\Carbon::parse($truck->pivot->assigned_date)->format('d/m/Y') }}</td>
                                <td class="small text-muted">{{ $truck->pivot->note ?? '-' }}</td>
                                <td>
                                    @if ($truck->pivot->released_date)
                                        <span class="badge bg-secondary">
                                            ถอนแล้ว
                                            {{ \Carbon\Carbon::parse($truck->pivot->released_date)->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="badge bg-success">กำลังทำงาน</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!$truck->pivot->released_date)
                                        <form method="POST"
                                            action="{{ route('camps.trucks.release', [$camp->id_camp, $truck->pivot->id_assignment]) }}"
                                            class="d-flex gap-1 justify-content-center">
                                            @csrf @method('PATCH')
                                            <input type="date" name="released_date"
                                                class="form-control form-control-sm" value="{{ date('Y-m-d') }}"
                                                required>
                                            <button class="btn btn-sm btn-outline-danger">ถอนออก</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">ยังไม่มีรถประจำแคมป์นี้</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($camp->latitude && $camp->longitude)
        <script>
            (() => {
                const campPosition = {
                    lat: {{ $camp->latitude }},
                    lng: {{ $camp->longitude }}
                };

                const campName = @json($camp->name_camp);
                const campAddress = @json($camp->full_address ?: '');
                const errorElement = document.getElementById('campMapError');

                window.initCampShowMap = function() {
                    const map = new google.maps.Map(document.getElementById('campShowMap'), {
                        center: campPosition,
                        zoom: 15,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: true,
                        gestureHandling: 'cooperative'
                    });

                    const marker = new google.maps.Marker({
                        map,
                        position: campPosition,
                        title: campName
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: '<div style="font-weight:600;margin-bottom:2px;">' + campName + '</div>' +
                            (campAddress ?
                                '<div style="font-size:12px;color:#666;">' + campAddress + '</div>' :
                                '')
                    });

                    infoWindow.open(map, marker);

                    marker.addListener('click', () => infoWindow.open(map, marker));
                };

                window.gm_authFailure = function() {
                    errorElement.textContent =
                        'Google Maps โหลดไม่ได้ กรุณาตรวจสอบ API Key, Billing และ Website restrictions';
                    errorElement.classList.remove('d-none');
                };
            })();
        </script>

        @if (config('services.google_maps.key'))
            <script async
                src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initCampShowMap&loading=async&language=th&region=TH">
            </script>
        @else
            <script>
                document.getElementById('campMapError').textContent =
                    'ยังไม่ได้ตั้งค่า GOOGLE_MAPS_API_KEY ในไฟล์ .env';
                document.getElementById('campMapError').classList.remove('d-none');
            </script>
        @endif
    @endif
@endsection
