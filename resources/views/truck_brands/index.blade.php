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
                <button class="btn btn-dark">ค้นหา</button>
                <a href="{{ route('truck_brands.index') }}" class="btn btn-outline-secondary">ล้าง</a>
                <a href="{{ route('truck_brands.create') }}" class="btn btn-dark float-end">เพิ่มยี่ห้อ</a>
            </div>
        </form>

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ยี่ห้อ</th>
                    <th class="text-center">จำนวนรุ่น</th>
                    <th class="text-end">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($brands as $brand)
                    <tr>
                        <td>{{ $brand->name_brand }}</td>
                        <td class="text-center">{{ $brand->models_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('truck_brands.edit', $brand) }}"
                                class="btn btn-sm btn-outline-secondary">แก้ไข</a>

                            <form method="POST" action="{{ route('truck_brands.destroy', $brand) }}" class="d-inline"
                                data-confirm="ยี่ห้อ {{ $brand->name_brand }} จะถูกลบออกจากระบบ"
                                data-confirm-title="ยืนยันการลบยี่ห้อรถบรรทุก" data-confirm-variant="danger"
                                data-confirm-ok="ลบข้อมูล">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">ยังไม่มีข้อมูล</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $brands->links() }}
    </div>
@endsection