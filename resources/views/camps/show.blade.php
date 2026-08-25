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
                        <a href="{{ route('camps.edit', $camp->id_camp) }}" class="btn btn-outline-primary btn-sm">
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
                    <div id="campShowMap" class="mt-3" style="width:100%;height:280px;border-radius:10px;"></div>
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
                                <input type="date" name="assigned_date" class="form-control"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small mb-1">หมายเหตุ</label>
                                <input type="text" name="note" class="form-control" placeholder="ไม่บังคับ">
                            </div>

                            <div class="col-md-2">
                                <button class="btn btn-dark w-100">เพิ่มรถ</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif

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
                                            <input type="date" name="released_date" class="form-control form-control-sm"
                                                value="{{ date('Y-m-d') }}" required>
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
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const lat = {{ $camp->latitude }};
                const lng = {{ $camp->longitude }};

                const showMap = L.map('campShowMap').setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(showMap);

                L.marker([lat, lng]).addTo(showMap)
                    .bindPopup(@json($camp->name_camp)).openPopup();

                setTimeout(function() {
                    showMap.invalidateSize();
                }, 200);
            });
        </script>
    @endif
@endsection