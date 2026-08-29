@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายละเอียดรถ {{ $truck->id_truck }}</h3>
    </div>
@endsection

@section('content')
    <div class="container py-2">

        {{-- @foreach (['ok' => 'success', 'info' => 'info'] as $key => $color)
            @if (session($key))
                <div class="alert alert-{{ $color }} shadow-sm">{{ session($key) }}</div>
            @endif
        @endforeach --}}

        @if ($errors->any())
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <a href="{{ route('trucks.index') }}" class="btn btn-sm btn-light mb-3">
            กลับหน้ารายการ
        </a>

        {{-- ข้อมูลรถ --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    {{-- แสดงรูปภาพรถ (ถ้ามี) --}}
                    @if ($truck->image)
                        <div class="col-md-3 mb-3 mb-md-0 text-center">
                            <img src="{{ asset('storage/' . $truck->image) }}" alt="{{ $truck->id_truck }}"
                                class="img-fluid rounded shadow-sm" style="max-height: 150px; object-fit: cover;">
                        </div>
                    @endif

                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <h5 class="mb-2">
                                    {{ $truck->brand->name_brand ?? '-' }} {{ $truck->model->name_model ?? '' }}
                                </h5>
                                <div class="text-muted small">
                                    ทะเบียน {{ $truck->id_truck }} ·
                                    ปี {{ $truck->year_truck ?? '-' }} ·
                                    {{ $truck->province_truck }}
                                </div>
                                <div class="text-muted small">
                                    อัตราสิ้นเปลือง {{ $truck->fuel_rate }} กม./ลิตร ·
                                    น้ำหนักรถ {{ $truck->weight_truck ? number_format($truck->weight_truck) . ' กก.' : '-' }}
                                </div>
                                @if ($truck->cubic_capacity)
                                    <div class="text-muted small">
                                        ความจุกระบะ {{ $truck->cubic_capacity }} คิว
                                    </div>
                                @endif
                            </div>

                            <div class="text-end">
                                <span class="badge bg-{{ $truck->status_color }} fs-6 mb-2">
                                    {{ $truck->status_label }}
                                </span>
                                <br>
                                <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#statusModal">
                                    เปลี่ยนสถานะรถ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- งานซ่อมที่ยังไม่ปิด --}}
        @if ($ongoing = $truck->ongoingMaintenance)
            <div class="card border-warning shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="mb-2">
                        <i class="bi bi-tools text-warning"></i> กำลังซ่อม: {{ $ongoing->title }}
                    </h6>

                    @if ($ongoing->detail)
                        <p class="small mb-2">{{ $ongoing->detail }}</p>
                    @endif

                    <p class="small text-muted mb-3">
                        เริ่ม {{ $ongoing->start_date ? $ongoing->start_date->format('d/m/Y') : '-' }}
                        @if ($ongoing->expected_return)
                            · คาดว่าเสร็จ {{ $ongoing->expected_return->format('d/m/Y') }}
                        @endif
                        @if ($ongoing->garage)
                            · อู่ {{ $ongoing->garage }}
                        @endif
                        @if ($ongoing->cost)
                            · ประเมิน {{ number_format($ongoing->cost, 2) }} บาท
                        @endif
                    </p>

                    <form method="POST" action="{{ route('maintenances.finish', $ongoing->id_maintenance) }}"
                        class="row g-2 align-items-end">
                        @csrf
                        @method('PATCH')

                        <div class="col-auto">
                            <label class="form-label small mb-1">วันที่ซ่อมเสร็จ</label>
                            <input type="date" name="finished_date" class="form-control form-control-sm"
                                value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-auto">
                            <label class="form-label small mb-1">ค่าซ่อมจริง (บาท)</label>
                            <input type="number" step="0.01" name="cost" class="form-control form-control-sm"
                                value="{{ $ongoing->cost }}">
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-success btn-sm">
                                ซ่อมเสร็จแล้ว
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ประวัติการซ่อม --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold py-3">ประวัติการซ่อมบำรุง</div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>วันที่เริ่ม</th>
                            <th>รายการ</th>
                            <th>อู่ / ผู้ซ่อม</th>
                            <th class="text-end">ค่าใช้จ่าย</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($truck->maintenances as $log)
                            <tr>
                                <td>{{ $log->start_date ? $log->start_date->format('d/m/Y') : '-' }}</td>
                                <td>
                                    {{ $log->title }}
                                    @if ($log->detail)
                                        <div class="small text-muted">{{ $log->detail }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->garage ?? '-' }}</td>
                                <td class="text-end">{{ $log->cost ? number_format($log->cost, 2) : '-' }}</td>
                                <td>
                                    @if ($log->finished_date)
                                        <span class="badge bg-success">
                                            เสร็จ {{ $log->finished_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">กำลังซ่อม</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">ยังไม่มีประวัติการซ่อม</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($truck->maintenances->isNotEmpty())
                <div class="card-footer bg-white text-end py-3">
                    ค่าซ่อมสะสมทั้งหมด
                    <strong class="text-primary fs-5 ms-1">{{ number_format($truck->maintenances->sum('cost'), 2) }}</strong>
                    บาท
                </div>
            @endif
        </div>
    </div>

    {{-- Modal เปลี่ยนสถานะ --}}
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('trucks.status', $truck->id_truck) }}" class="modal-content">
                @csrf
                @method('PATCH')

                <div class="modal-header">
                    <h5 class="modal-title">เปลี่ยนสถานะรถ {{ $truck->id_truck }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    @foreach (\App\Models\Truck::STATUS_LABELS as $value => $label)
                        <div class="form-check mb-2">
                            <input class="form-check-input status-radio" type="radio" name="status_truck"
                                value="{{ $value }}" id="st_{{ $value }}"
                                @checked(old('status_truck', $truck->status_truck) === $value)>
                            <label class="form-check-label" for="st_{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach

                    <div id="maintenanceFields" class="border-top pt-3 mt-3 d-none">
                        <div class="mb-2">
                            <label class="form-label">ซ่อมอะไร <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                                placeholder="เช่น เปลี่ยนยาง 6 เส้น">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">รายละเอียดเพิ่มเติม</label>
                            <textarea name="detail" rows="2" class="form-control">{{ old('detail') }}</textarea>
                        </div>

                        <div class="row g-2">
                            <div class="col-6 mb-2">
                                <label class="form-label">วันที่เริ่มซ่อม <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ old('start_date', date('Y-m-d')) }}">
                            </div>
                            {{-- <div class="col-6 mb-2">
                                <label class="form-label">คาดว่าเสร็จ</label>
                                <input type="date" name="expected_return" class="form-control"
                                    value="{{ old('expected_return') }}">
                            </div> --}}
                            <div class="col-6 mb-2">
                                <label class="form-label">อู่ / ผู้ซ่อม</label>
                                <input type="text" name="garage" class="form-control" value="{{ old('garage') }}">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">ค่าซ่อมโดยประมาณ</label>
                                <input type="number" step="0.01" name="cost" class="form-control"
                                    value="{{ old('cost') }}">
                            </div>
                        </div>
                    </div>

                    <div id="retireFields" class="border-top pt-3 mt-3 d-none">
                        <label class="form-label">เหตุผลที่ปลดประจำการ <span class="text-danger">*</span></label>
                        <textarea name="retire_reason" rows="2" class="form-control" placeholder="เช่น อายุการใช้งานเกิน ค่าซ่อมไม่คุ้ม">{{ old('retire_reason') }}</textarea>
                        <div class="form-text text-danger">
                            รถที่ปลดประจำการจะไม่ปรากฏในรายการเลือกใช้งาน
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-dark">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const radios = document.querySelectorAll('.status-radio');
            const maintBox = document.getElementById('maintenanceFields');
            const retireBox = document.getElementById('retireFields');

            const toggleFields = () => {
                const picked = document.querySelector('.status-radio:checked')?.value;
                if (maintBox) maintBox.classList.toggle('d-none', picked !== 'maintenance');
                if (retireBox) retireBox.classList.toggle('d-none', picked !== 'retired');
            };

            radios.forEach(r => r.addEventListener('change', toggleFields));
            toggleFields();

            @if ($errors->any())
                const modalEl = document.getElementById('statusModal');
                if (modalEl) {
                    new bootstrap.Modal(modalEl).show();
                }
            @endif
        });
    </script>
@endsection