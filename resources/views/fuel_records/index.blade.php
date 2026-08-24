@extends('layout')

@section('namepage')
    <div class="container">
        <h3>บันทึกน้ำมันรถบรรทุก</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">
        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif


        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('fuel_records.create') }}" class="btn btn-dark">เพิ่มบันทึกน้ำมัน</a>
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>วันที่</th>
                        <th>รถบรรทุก</th>
                        <th>จุดเริ่มต้น</th>
                        <th>ปลายทาง</th>
                        <th>ระยะทาง (km)</th>
                        <th>ค่าน้ำมัน</th>
                        <th>ค่าน้ำมันทั้งหมด</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $r)
                        <tr>
                            <td>{{ $r->date_record }}</td>
                            <td>{{ $r->truck->brand_truck }} ({{ $r->trucks_id_truck }})</td>
                            <td>{{ $r->start_point }}</td>
                            <td>{{ $r->destination }}</td>
                            <td>{{ $r->distance }}</td>
                            <td>{{ $r->cost_fuel }}</td>
                            <td>{{ $r->cost_fuel_total }}</td>
                            <td>
                                <a href="{{ route('fuel_records.edit', $r->id_fuel_record) }}"
                                    class="btn btn-sm btn-primary">แก้ไข</a>
                                <form method="POST" action="{{ route('fuel_records.destroy', $item) }}" class="d-inline"
                                    data-confirm="ข้อมูล [ชื่อรายการ] จะถูกลบถาวร ไม่สามารถกู้คืนได้"
                                    data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger"
                                    data-confirm-ok="ลบข้อมูล">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">— ไม่พบข้อมูล —</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $records->links() }}
        </div>
    </div>
@endsection
