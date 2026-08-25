@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แผนงานขนส่ง</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

  

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                    placeholder="ค้นหาลูกค้า / ต้นทาง / ปลายทาง">
                <button class="btn btn-outline-secondary">ค้นหา</button>
            </form>

            <a href="{{ route('transport-jobs.create') }}" class="btn btn-dark">
                + เพิ่มแผนงาน
            </a>
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ลูกค้า</th>
                        <th>ช่วงวันที่</th>
                        <th>เส้นทาง</th>
                        <th>ระยะทาง (กม.)</th>
                        <th style="width:160px">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $j)
                        <tr onclick="window.location='{{ route('transport-jobs.show', $j) }}'" style="cursor:pointer">

                            <td class="fw-semibold">
                                {{ $j->customer?->name_customer ?? '-' }}
                            </td>

                            <td>
                                {{ $j->start_date }}
                                @if ($j->end_date)
                                    – {{ $j->end_date }}
                                @endif
                            </td>

                            <td>
                                {{ $j->start_point }} → {{ $j->destination }}
                            </td>

                            <td>
                                {{ number_format($j->distance_km, 2) }}
                            </td>

                            <td onclick="event.stopPropagation()">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('transport-jobs.edit', $j) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        แก้ไข
                                    </a>

                                    <form method="POST" action="{{ route('transport_jobs.destroy', $j) }}" class="d-inline"
                                        data-confirm="ข้อมูล {{ $j->transport_job }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบข้อมูล" 
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty

                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                — ยังไม่มีแผนงาน —
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $jobs->links() }}
        </div>

    </div>
@endsection
