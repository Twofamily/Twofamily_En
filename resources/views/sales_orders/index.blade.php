@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบสั่งขาย</h3>
    </div>
@endsection

@section('content')
<div class="container py-3">

    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('sales-orders.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">ค้นหา</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control"
                           placeholder="เลขที่ใบสั่งขาย / เลข PO / ชื่อลูกค้า">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">ลูกค้า</label>
                    <select name="customer" class="form-select">
                        <option value="">— ทั้งหมด —</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id_customer }}"
                                @selected($customerId == $customer->id_customer)>
                                {{ $customer->name_customer }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">สถานะ</label>
                    <select name="status" class="form-select">
                        <option value="">— ทั้งหมด —</option>
                        @foreach (\App\Models\SalesOrder::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-dark flex-fill text-nowrap">
                        <i class="bi bi-search me-1"></i>
                        ค้นหา
                    </button>

                    <a href="{{ route('sales-orders.index') }}"
                       class="btn btn-outline-secondary text-nowrap">
                        ล้าง
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-end align-items-center mb-3">
        <a href="{{ route('sales-orders.create') }}" class="btn btn-dark text-nowrap">
            <i class="bi bi-plus-lg me-1"></i>
            สร้างใบสั่งขาย
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>เลขที่</th>
                        <th>วันที่สั่ง</th>
                        <th>ลูกค้า</th>
                        <th>อ้างอิง</th>
                        <th class="text-end">ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>แคมป์</th>
                        <th class="text-center" style="width:100px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salesOrders as $so)
                        <tr>
                            <td class="fw-semibold">{{ $so->code_so }}</td>

                            <td>{{ $so->order_date?->format('d/m/Y') ?? '-' }}</td>

                            <td>{{ $so->customer->name_customer ?? '-' }}</td>

                            <td>
                                @if ($so->quotation)
                                    <a href="{{ route('quotations.show', $so->quotation) }}"
                                       class="text-decoration-none small">
                                        {{ $so->quotation->code_quot }}
                                    </a>
                                @else
                                    <span class="text-muted small">สั่งตรง</span>
                                @endif
                            </td>

                            <td class="text-end">{{ number_format($so->total_amount, 2) }}</td>

                            <td>
                                <span class="badge bg-{{ $so->status_color }}">{{ $so->status_label }}</span>
                            </td>

                            <td>
                                @if ($so->camp)
                                    <a href="{{ route('camps.show', $so->camp) }}"
                                       class="text-decoration-none small">
                                        {{ $so->camp->code_camp }}
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('sales-orders.show', $so) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูใบสั่งขาย {{ $so->code_so }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ยังไม่มีใบสั่งขาย
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $salesOrders->withQueryString()->links() }}
    </div>
</div>
@endsection