@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ยี่ห้อรถบรรทุก</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">

        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหายี่ห้อ">
            </div>

            <div class="col-md-8">
                <button class="btn btn-dark text-nowrap">
                    <i class="bi bi-search me-1"></i>
                    ค้นหา
                </button>

                <a href="{{ route('truck_brands.index') }}"
                    class="btn btn-outline-secondary text-nowrap">
                    <i class="bi bi-x-circle me-1"></i>
                    ล้าง
                </a>

                <a href="{{ route('truck_brands.create') }}"
                    class="btn btn-dark float-end text-nowrap">
                    <i class="bi bi-plus-lg me-1"></i>
                    เพิ่มยี่ห้อ
                </a>
            </div>
        </form>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ยี่ห้อ</th>
                        <th class="text-center">จำนวนรุ่น</th>
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td>{{ $brand->name_brand }}</td>

                            <td class="text-center">{{ $brand->models_count }}</td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- แก้ไขข้อมูล --}}
                                    <a href="{{ route('truck_brands.edit', $brand) }}"
                                        class="btn btn-outline-primary action-button"
                                        title="แก้ไขข้อมูล"
                                        aria-label="แก้ไขยี่ห้อ {{ $brand->name_brand }}">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล --}}
                                    <form method="POST"
                                        action="{{ route('truck_brands.destroy', $brand) }}"
                                        class="delete-form"
                                        data-confirm="ยี่ห้อ {{ $brand->name_brand }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบยี่ห้อรถบรรทุก"
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-outline-danger action-button"
                                            type="submit"
                                            title="ลบข้อมูล"
                                            aria-label="ลบยี่ห้อ {{ $brand->name_brand }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ยังไม่มีข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $brands->withQueryString()->links() }}
        </div>
    </div>
@endsection