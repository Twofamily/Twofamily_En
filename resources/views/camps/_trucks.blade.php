{{--
    จัดการรถบรรทุก + คนขับประจำแคมป์ (เพิ่มรถพร้อมคนขับ / เปลี่ยนคนขับ / ถอนรถออก)
    ใช้ในหน้าแก้ไขแคมป์เท่านั้น — หน้าดูข้อมูล (camps.show) แสดงรายการแบบ read-only

    กติกา: รถ 1 คัน และคนขับ 1 คน ทำงานได้ทีละแคมป์

    ต้องการ: $camp (โหลด trucks มาแล้ว), $availableTrucks, $availableDrivers
--}}
<div class="card border-0 shadow-sm mt-4" id="camp-trucks">
    <div class="card-body p-4">
        <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">
            รถบรรทุกและคนขับประจำแคมป์
        </h6>

        {{-- เพิ่มรถเข้าแคมป์ (เฉพาะแคมป์ที่เปิดอยู่) --}}
        @if ($camp->status_camp === 'active')
            @if ($availableTrucks->isEmpty())
                <div class="text-muted small mb-3">
                    ไม่มีรถที่พร้อมใช้งานเหลืออยู่ (รถอาจอยู่ระหว่างซ่อมบำรุง หรือติดงานที่แคมป์อื่น)
                </div>
            @else
                <form method="POST" action="{{ route('camps.trucks.assign', $camp->id_camp) }}"
                    class="row g-2 align-items-end mb-4"
                    data-confirm="ต้องการเพิ่มรถคันนี้เข้าแคมป์ {{ $camp->name_camp }} หรือไม่"
                    data-confirm-title="ยืนยันการเพิ่มรถเข้าแคมป์"
                    data-confirm-ok="เพิ่มรถ"
                    data-confirm-variant="primary">
                    @csrf

                    <div class="col-md-3">
                        <label class="form-label small mb-1">เลือกรถ</label>
                        <select name="id_truck" class="form-select" required data-required-msg="กรุณาเลือกรถ">
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
                        <label class="form-label small mb-1">คนขับ <span class="text-muted">(ไม่บังคับ)</span></label>
                        <select name="id_driver" class="form-select">
                            <option value="">— ยังไม่ระบุ —</option>
                            @foreach ($availableDrivers ?? [] as $driver)
                                <option value="{{ $driver->id_driver }}">
                                    {{ $driver->full_name }}{{ $driver->phone_driver ? ' · ' . $driver->phone_driver : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small mb-1">วันที่เริ่มทำงาน</label>
                        <input type="date" name="assigned_date" class="form-control" value="{{ date('Y-m-d') }}"
                            required data-required-msg="กรุณาใส่วันที่เริ่มทำงาน">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small mb-1">หมายเหตุ</label>
                        <input type="text" name="note" class="form-control" placeholder="ไม่บังคับ" maxlength="255">
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100">+ เพิ่มรถ</button>
                    </div>
                </form>
            @endif
        @else
            <div class="alert alert-secondary small">
                แคมป์นี้ปิดงานแล้ว จึงเพิ่มรถใหม่ไม่ได้ (เปลี่ยนสถานะเป็นเปิดใช้งานก่อนหากต้องการเพิ่มรถ)
            </div>
        @endif

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ทะเบียน</th>
                        <th>ยี่ห้อ / รุ่น</th>
                        <th style="min-width: 220px;">คนขับ</th>
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
                            <td>
                                @if (! $truck->pivot->released_date)
                                    {{-- เปลี่ยน/ใส่คนขับ: ตัวเลือก = คนขับปัจจุบัน + คนขับที่ว่าง --}}
                                    <form method="POST"
                                        action="{{ route('camps.trucks.driver', [$camp->id_camp, $truck->pivot->id_assignment]) }}"
                                        class="d-flex gap-1"
                                        data-confirm="ต้องการเปลี่ยนคนขับของรถ {{ $truck->id_truck }} หรือไม่"
                                        data-confirm-title="ยืนยันการเปลี่ยนคนขับ"
                                        data-confirm-ok="บันทึก"
                                        data-confirm-variant="primary">
                                        @csrf @method('PATCH')
                                        <select name="id_driver" class="form-select form-select-sm"
                                            aria-label="คนขับของรถ {{ $truck->id_truck }}">
                                            <option value="">— ยังไม่ระบุ —</option>
                                            @if ($truck->pivot->driver)
                                                <option value="{{ $truck->pivot->id_driver }}" selected>
                                                    {{ $truck->pivot->driver->full_name }}
                                                </option>
                                            @endif
                                            @foreach ($availableDrivers ?? [] as $driver)
                                                <option value="{{ $driver->id_driver }}">{{ $driver->full_name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">บันทึก</button>
                                    </form>
                                @else
                                    {{ $truck->pivot->driver->full_name ?? '-' }}
                                @endif
                            </td>
                            <td>{{ thai_date($truck->pivot->assigned_date) }}</td>
                            <td class="small text-muted">{{ $truck->pivot->note ?? '-' }}</td>
                            <td>
                                @if ($truck->pivot->released_date)
                                    <span class="badge bg-secondary">
                                        ถอนแล้ว {{ thai_date($truck->pivot->released_date) }}
                                    </span>
                                @else
                                    <span class="badge bg-success">กำลังทำงาน</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if (! $truck->pivot->released_date)
                                    <form method="POST"
                                        action="{{ route('camps.trucks.release', [$camp->id_camp, $truck->pivot->id_assignment]) }}"
                                        class="d-flex gap-1 justify-content-center"
                                        data-confirm="ต้องการถอนรถ {{ $truck->id_truck }} ออกจากแคมป์นี้หรือไม่"
                                        data-confirm-title="ยืนยันการถอนรถออก"
                                        data-confirm-ok="ถอนออก"
                                        data-confirm-variant="danger">
                                        @csrf @method('PATCH')
                                        <input type="date" name="released_date"
                                            class="form-control form-control-sm" value="{{ date('Y-m-d') }}"
                                            min="{{ \Carbon\Carbon::parse($truck->pivot->assigned_date)->format('Y-m-d') }}"
                                            required data-required-msg="กรุณาใส่วันที่ถอนรถ"
                                            aria-label="วันที่ถอนรถ {{ $truck->id_truck }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">ถอนออก</button>
                                    </form>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">ยังไม่มีรถประจำแคมป์นี้</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
