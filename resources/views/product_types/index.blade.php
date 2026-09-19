@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ประเภทสินค้าทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <div class="d-flex justify-content-end align-items-center mb-3">
            <a href="{{ route('product_types.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มประเภทสินค้า
            </a>
        </div>

        {{-- ตารางแสดงข้อมูล --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อประเภทสินค้า</th>
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $t)
                        <tr>
                            <td>{{ $t->name_product_type }}</td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- แก้ไขข้อมูล --}}
                                    <a href="{{ route('product_types.edit', $t) }}"
                                        class="btn btn-outline-primary action-button"
                                        title="แก้ไขข้อมูล"
                                        aria-label="แก้ไขประเภทสินค้า {{ $t->name_product_type }}">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล --}}
                                    <form method="POST"
                                        action="{{ route('product_types.destroy', $t) }}"
                                        class="delete-form"
                                        data-confirm="ข้อมูล {{ $t->name_product_type }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบข้อมูล"
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-outline-danger action-button"
                                            type="submit"
                                            title="ลบข้อมูล"
                                            aria-label="ลบประเภทสินค้า {{ $t->name_product_type }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ไม่พบข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- แสดง pagination --}}
        <div class="mt-3">
            {{ $types->withQueryString()->links() }}
        </div>
    </div>
@endsection