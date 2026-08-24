@extends('layout')

@section('namepage')
    <div class="container">
        <h3>สินค้าทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        @if (session('ok'))
            <div class="alert alert-success shadow-sm">{{ session('ok') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-end mb-3">

            <form class="d-flex gap-2" method="GET" action="{{ route('products.index') }}">
                <input type="text" name="q" class="form-control" style="min-width:240px" value="{{ $q }}"
                    placeholder="ค้นหาชื่อ/รายละเอียด…">

                <button class="btn btn-outline-secondary" type="submit">
                    ค้นหา
                </button>
            </form>


            <a href="{{ route('products.create') }}" class="btn btn-dark">
                + เพิ่มสินค้า
            </a>
        </div>


        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อสินค้า</th>
                        <th>ประเภท</th>
                        <th class="text-end" style="width:140px;">ราคาต่อหน่วย (บาท / คิว)</th>
                        <th style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $p->name_product }}</div>
                                @if ($p->detail_product)
                                    <div class="small text-muted">{{ Str::limit($p->detail_product, 80) }}</div>
                                @endif
                            </td>
                            <td>{{ $p->type->name_product_type ?? '-' }}</td>
                            <td class="text-end">{{ number_format((int) $p->unit_price) }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('products.edit', $p) }}"
                                        class="btn btn-sm btn-outline-primary">แก้ไข</a>
                                    <form method="POST" action="{{ route('products.destroy', $item) }}" class="d-inline"
                                        data-confirm="ข้อมูล [ชื่อรายการ] จะถูกลบถาวร ไม่สามารถกู้คืนได้"
                                        data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">— ไม่พบข้อมูล —</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $products->links() }}
        </div>
    </div>
@endsection
