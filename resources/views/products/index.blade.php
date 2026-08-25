@extends('layout')

@section('namepage')
    <div class="container">
        <h3>สินค้าทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

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
                        <th style="width: 80px;" class="text-center">รูปภาพ</th>
                        <th>ชื่อสินค้า</th>
                        <th>ประเภท</th>
                        <th class="text-end" style="width:140px;">ราคาต่อหน่วย (บาท / คิว)</th>
                        <th style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        <tr>
                            <!-- คอลัมน์รูปภาพสินค้า -->
                            <td class="text-center">
                                @if ($p->image)
                                    <img src="{{ asset('storage/' . $p->image) }}" alt="{{ $p->name_product }}"
                                        class="rounded border shadow-sm"
                                        style="width: 60px; height: 60px; object-fit: cover;">
                                @else
                                    <div class="bg-light text-muted rounded d-flex align-items-center justify-content-center border mx-auto"
                                        style="width: 60px; height: 60px; font-size: 0.75rem;">
                                        ไม่มีรูป
                                    </div>
                                @endif
                            </td>
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
                                    <form method="POST" action="{{ route('products.destroy', $p) }}" class="d-inline"
                                        data-confirm="ข้อมูล {{ $p->name_product }} จะถูกลบออกจากระบบ"
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