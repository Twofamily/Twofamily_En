@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รถบรรทุกทั้งหมด</h3>
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
    </style>

    <div class="container py-3">

        @if (session('info'))
            <div class="alert alert-info shadow-sm">{{ session('info') }}</div>
        @endif

        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหา: ทะเบียน/ยี่ห้อ/รุ่น" />

                <select name="status" class="form-select">
                    <option value="">ทุกสถานะ</option>
                    @foreach (\App\Models\Truck::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <button class="btn btn-outline-secondary text-nowrap" type="submit">
                    <i class="bi bi-search me-1"></i>
                    ค้นหา
                </button>
            </form>

            <a href="{{ route('trucks.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มรถบรรทุก
            </a>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>เลขทะเบียน</th>
                            <th>ยี่ห้อ</th>
                            <th>รุ่น</th>
                            <th>ปีที่ซื้อ</th>
                            <th>จังหวัด</th>
                            <th>สถานะ</th>
                            <th class="action-col text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trucks as $t)
                            <tr>
                                <td class="fw-semibold">{{ $t->id_truck }}</td>
                                <td>{{ $t->brand->name_brand ?? '-' }}</td>
                                <td>{{ $t->model->name_model ?? '-' }}</td>
                                <td>{{ $t->year_truck }}</td>
                                <td>{{ $t->province_truck }}</td>

                                <td>
                                    <span class="badge bg-{{ $t->status_color }}">
                                        {{ $t->status_label }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <div class="action-buttons">
                                        {{-- ดูข้อมูล --}}
                                        <a href="{{ route('trucks.show', $t->id_truck) }}"
                                            class="btn action-button action-view"
                                            title="ดูข้อมูล"
                                            aria-label="ดูข้อมูลรถบรรทุก {{ $t->id_truck }}">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>

                                        {{-- แก้ไขข้อมูล --}}
                                        <a href="{{ route('trucks.edit', $t->id_truck) }}"
                                            class="btn btn-outline-primary action-button"
                                            title="แก้ไขข้อมูล"
                                            aria-label="แก้ไขข้อมูลรถบรรทุก {{ $t->id_truck }}">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        </a>

                                        {{-- ลบข้อมูล --}}
                                        <form method="POST"
                                            action="{{ route('trucks.destroy', $t->id_truck) }}"
                                            class="delete-form"
                                            data-confirm="รถบรรทุกทะเบียน {{ $t->id_truck }} จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบรถบรรทุก"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="btn btn-outline-danger action-button"
                                                title="ลบข้อมูล"
                                                aria-label="ลบข้อมูลรถบรรทุก {{ $t->id_truck }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    ไม่พบข้อมูล
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $trucks->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection