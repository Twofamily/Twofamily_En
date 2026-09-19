@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ลูกค้าทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" style="max-width: 500px;">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหาชื่อ / เบอร์ / อีเมล">
                <button class="btn btn-outline-secondary text-nowrap">
                    <i class="bi bi-search me-1"></i>
                    ค้นหา
                </button>
            </form>

            @if (auth()->user()->canEdit())
                <a href="{{ route('customers.create') }}" class="btn btn-dark text-nowrap">
                    <i class="bi bi-plus-lg me-1"></i>
                    เพิ่มลูกค้า
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
                        <th class="action-col text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $c->name_customer }}</div>

                                @if ($c->email_customer)
                                    <div class="small text-muted">
                                        <i class="bi bi-envelope me-1"></i>
                                        {{ $c->email_customer }}
                                    </div>
                                @endif
                            </td>

                            <td>{{ $c->customer_type == 'company' ? 'บริษัท' : 'บุคคล' }}</td>
                            <td>{{ $c->phone_customer ?: '-' }}</td>
                            <td>{{ $c->province ?: '-' }}</td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('customers.show', $c->id_customer) }}"
                                        class="btn action-button action-view" title="ดูข้อมูล"
                                        aria-label="ดูข้อมูลลูกค้า {{ $c->name_customer }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    @if (auth()->user()->canEdit())
                                        {{-- แก้ไขข้อมูล --}}
                                        <a href="{{ route('customers.edit', $c->id_customer) }}"
                                            class="btn btn-outline-primary action-button" title="แก้ไขข้อมูล"
                                            aria-label="แก้ไขข้อมูลลูกค้า {{ $c->name_customer }}">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        </a>

                                        {{-- ลบข้อมูล --}}
                                        <form method="POST" action="{{ route('customers.destroy', $c->id_customer) }}"
                                            class="delete-form"
                                            data-confirm="ข้อมูล {{ $c->name_customer }} จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-outline-danger action-button" type="submit"
                                                title="ลบข้อมูล" aria-label="ลบข้อมูลลูกค้า {{ $c->name_customer }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ไม่พบข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->withQueryString()->links() }}

    </div>
@endsection
