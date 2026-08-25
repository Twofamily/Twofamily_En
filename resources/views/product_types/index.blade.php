@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ประเภทสินค้าทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">


        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('product_types.create') }}" class="btn btn-dark">
                + เพิ่มประเภทสินค้า
            </a>
        </div>


        {{-- ตารางแสดงข้อมูล --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อประเภทสินค้า</th>
                        <th style="width:120px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $t)
                        <tr>
                            <td>{{ $t->name_product_type }}</td>
                            <td>
                                <a href="{{ route('product_types.edit', $t) }}"
                                    class="btn btn-sm btn-outline-primary">แก้ไข</a>

                                <form method="POST" action="{{ route('product_types.destroy', $t) }}" class="d-inline"
                                    data-confirm="ข้อมูล {{ $t->name_product_type }} จะถูกลบออกจากระบบ"
                                    data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger"
                                    data-confirm-ok="ลบข้อมูล">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted">— ไม่พบข้อมูล —</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- แสดง pagination --}}
        <div class="mt-3">
            {{ $types->links() }}
        </div>
    </div>
@endsection
