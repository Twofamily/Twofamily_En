@extends('layout')

@section('namepage')
    <div class="container">
        <h3>พนักงานขับรถทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">



        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" method="GET" action="{{ route('drivers.index') }}">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหาชื่อ / เบอร์ / เลขบัตร">
                <button class="btn btn-outline-secondary">ค้นหา</button>
            </form>

            <a href="{{ route('drivers.create') }}" class="btn btn-dark">
                + เพิ่มพนักงานขับรถ
            </a>
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อ</th>
                        <th>ที่อยู่</th>
                        <th>เบอร์</th>
                        <th>เลขบัตร</th>
                        <th style="width:160px">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $d)
                        <tr onclick="window.location='{{ route('drivers.show', $d) }}'" style="cursor:pointer">

                            <td>{{ $d->fname_driver }} {{ $d->lname_driver }}</td>
                            <td class="small text-muted">
                                {{ $d->address_detail }}
                                {{ $d->subdistrict }}
                                {{ $d->district }}
                                {{ $d->province }}
                                {{ $d->zipcode }}
                            </td>

                            <td>{{ $d->phone_driver ?: '-' }}</td>
                            <td>{{ $d->citizenid_driver ?: '-' }}</td>

                            {{-- ปุ่มต้องกันไม่ให้ trigger onclick --}}
                            <td onclick="event.stopPropagation()">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('drivers.edit', $d) }}" class="btn btn-sm btn-outline-primary">
                                        แก้ไข
                                    </a>

                                    <form method="POST" action="{{ route('drivers.destroy', $d) }}"class="d-inline"
                                        data-confirm="ข้อมูล {{ $d->lname_driver }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบข้อมูล" 
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            ลบ
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                — ไม่พบข้อมูล —
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $drivers->links() }}
    </div>
@endsection
