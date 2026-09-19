@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รุ่นรถบรรทุก</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">

        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหารุ่น">
            </div>

            <div class="col-md-3">
                <select name="brand" class="form-select">
                    <option value="">— ทุกยี่ห้อ —</option>
                    @foreach ($brands as $b)
                        <option value="{{ $b->id }}" @selected($brandId == $b->id)>
                            {{ $b->name_brand }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <button class="btn btn-dark text-nowrap">
                    <i class="bi bi-search me-1"></i>
                    ค้นหา
                </button>

                <a href="{{ route('truck_models.index') }}"
                    class="btn btn-outline-secondary text-nowrap">
                    <i class="bi bi-x-circle me-1"></i>
                    ล้าง
                </a>

                <a href="{{ route('truck_models.create') }}"
                    class="btn btn-dark float-end text-nowrap">
                    <i class="bi bi-plus-lg me-1"></i>
                    เพิ่มรุ่น
                </a>
            </div>
        </form>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ยี่ห้อ</th>
                        <th>รุ่น</th>
                        <th class="text-center">ปี</th>
                        <th class="text-center">ล้อ</th>
                        <th class="text-end">คิว</th>
                        <th class="text-end">กม./ลิตร</th>
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($models as $m)
                        <tr class="{{ $m->is_active ? '' : 'text-muted' }}">
                            <td>{{ $m->brand->name_brand ?? '-' }}</td>
                            <td>{{ $m->name_model }}</td>
                            <td class="text-center">{{ $m->model_year ?? '-' }}</td>
                            <td class="text-center">{{ $m->wheels ?? '-' }}</td>
                            <td class="text-end">{{ $m->cubic_capacity ?? '-' }}</td>
                            <td class="text-end">{{ $m->fuel_rate ?? '-' }}</td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- แก้ไขข้อมูล --}}
                                    <a href="{{ route('truck_models.edit', $m) }}"
                                        class="btn btn-outline-primary action-button"
                                        title="แก้ไขข้อมูล"
                                        aria-label="แก้ไขรุ่น {{ $m->name_model }}">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล --}}
                                    <form method="POST"
                                        action="{{ route('truck_models.destroy', $m) }}"
                                        class="delete-form"
                                        data-confirm="รุ่น {{ $m->name_model }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบรุ่นรถบรรทุก"
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-outline-danger action-button"
                                            type="submit"
                                            title="ลบข้อมูล"
                                            aria-label="ลบรุ่น {{ $m->name_model }}">
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
                                ยังไม่มีข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $models->withQueryString()->links() }}
        </div>
    </div>
@endsection