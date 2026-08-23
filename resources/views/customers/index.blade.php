@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ลูกค้าทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        <div class="d-flex justify-content-between mb-3">
            <form class="d-flex gap-2">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหาชื่อ / เบอร์ / อีเมล">
                <button class="btn btn-outline-secondary">ค้นหา</button>
            </form>

            @if (auth()->user()->canEdit())
                <a href="{{ route('customers.create') }}" class="btn btn-dark">
                    + เพิ่มลูกค้า
                </a>
            @endif
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อลูกค้า</th>
                        <th>ประเภท</th>
                        <th>เบอร์</th>
                        <th>จังหวัด</th>
                        <th style="width:160px">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                        <tr onclick="window.location='{{ route('customers.show', $c) }}'" style="cursor:pointer">

                            <td>
                                <div class="fw-semibold">{{ $c->name_customer }}</div>

                                @if ($c->email_customer)
                                    <div class="small text-muted">📧 {{ $c->email_customer }}</div>
                                @endif
                            </td>

                            <td>{{ $c->customer_type == 'company' ? 'บริษัท' : 'บุคคล' }}</td>
                            <td>{{ $c->phone_customer ?: '-' }}</td>
                            <td>{{ $c->province ?: '-' }}</td>

                            <td onclick="event.stopPropagation()">
                                @if (auth()->user()->canEdit())
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('customers.edit', $c) }}"
                                            class="btn btn-sm btn-outline-primary">แก้ไข</a>

                                        <form method="POST" action="{{ route('customers.destroy', $c) }}"
                                            onsubmit="event.stopPropagation(); return confirm('ยืนยันลบข้อมูล?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">ลบ</button>
                                        </form>
                                    </div>
                                @endif
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

        {{ $customers->links() }}

    </div>
@endsection
