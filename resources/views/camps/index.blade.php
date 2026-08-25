@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แคมป์งานทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <style>
        .table-card {
            border: 1px solid rgba(0, 0, 0, .08);
            box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
            border-radius: 14px;
            overflow: hidden;
        }

        .table thead th {
            background: #f7f7f9;
        }

        .action-col {
            width: 180px;
        }

        .addr-col {
            max-width: 260px;
        }
    </style>

    <div class="container py-3">


        @if (session('info'))
            <div class="alert alert-info shadow-sm">{{ session('info') }}</div>
        @endif

        <div class="row g-2 align-items-center mb-3">
            <div class="col-md-10">
                <form method="GET" class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="q" value="{{ $q }}" class="form-control"
                            placeholder="ค้นหา: ชื่อแคมป์ / รหัส / ลูกค้า">
                    </div>

                    <div class="col-md-3">
                        <select name="customer" class="form-select">
                            <option value="">ทั้งหมด</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id_customer }}" @selected($customerId == $c->id_customer)>
                                    {{ $c->name_customer }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">ทุกสถานะ</option>
                            @foreach (\App\Models\Camp::STATUS_LABELS as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" type="submit">ค้นหา</button>
                    </div>
                </form>
            </div>

            <div class="col-md-2 text-end">
                <a href="{{ route('camps.create') }}" class="btn btn-dark">เพิ่มแคมป์</a>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อแคมป์</th>
                            <th>ลูกค้า</th>
                            <th>ที่ตั้ง</th>
                            <th>ผู้ติดต่อ</th>
                            <th class="text-center">รถ</th>
                            <th>สถานะ</th>
                            <th class="action-col text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($camps as $camp)
                            <tr>
                                <td class="fw-semibold">{{ $camp->code_camp }}</td>
                                <td>{{ $camp->name_camp }}</td>
                                <td>{{ $camp->customer->name_customer ?? '-' }}</td>
                                <td class="small addr-col">{{ $camp->full_address ?: '-' }}</td>
                                <td class="small">
                                    {{ $camp->contact_name ?? '-' }}
                                    @if ($camp->contact_phone)
                                        <div class="text-muted">{{ $camp->contact_phone }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark">
                                        {{ $camp->trucks_count }} คัน
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $camp->status_camp === 'active' ? 'success' : 'secondary' }}">
                                        {{ $camp->status_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('camps.show', $camp->id_camp) }}"
                                            class="btn btn-sm btn-outline-dark">ดู</a>

                                        <a href="{{ route('camps.edit', $camp->id_camp) }}"
                                            class="btn btn-sm btn-outline-primary">แก้ไข</a>

                                        <form method="POST" action="{{ route('camps.destroy', $camp) }}" class="d-inline"
                                            data-confirm="ข้อมูล {{ $camp->code_camp }} จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบข้อมูล" 
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">ไม่พบข้อมูล</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $camps->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
