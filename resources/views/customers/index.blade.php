@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ลูกค้าทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <x-search-bar :action="route('customers.index')"
                      placeholder="ค้นหาชื่อ / ประเภท / เบอร์โทร / อีเมล / ที่อยู่ / จังหวัด"
                      :search="$search" :per-page="$perPage"
                      :statuses="$typeLabels" :status="$type" status-name="type" status-label="ประเภทลูกค้า" />

        @if (auth()->user()->canEdit())
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('customers.create') }}" class="btn btn-dark text-nowrap">
                    <i class="bi bi-plus-lg me-1"></i>
                    เพิ่มลูกค้า
                </a>
            </div>
        @endif

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
                                @if (auth()->user()->canEdit())
                                    <x-row-actions :show="route('customers.show', $c->id_customer)"
                                                   :edit="route('customers.edit', $c->id_customer)"
                                                   :delete="route('customers.destroy', $c->id_customer)"
                                                   :label="$c->name_customer" />
                                @else
                                    <x-row-actions :show="route('customers.show', $c->id_customer)" :label="$c->name_customer" />
                                @endif
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

        @include('partials.pagination-footer', ['paginator' => $customers])

    </div>
@endsection
