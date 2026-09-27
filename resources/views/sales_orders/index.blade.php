@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบสั่งขาย</h3>
    </div>
@endsection

@php
    $hasFilter = $search !== '' || $status !== null || $customerId !== null;
@endphp

@section('content')
    <div class="container py-3">

        {{-- ==================== แถบค้นหา / กรอง / จำนวนต่อหน้า ==================== --}}
        {{-- form เดียวครอบทั้งหมด เพื่อให้ค่าทุกตัวถูกส่งไปพร้อมกัน --}}
        <form method="GET" action="{{ route('sales-orders.index') }}" class="mb-3">
            <div class="row g-2 align-items-center">

                {{-- ช่องค้นหา --}}
                <div class="col-12 col-lg-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="เลขที่ / เลข PO / ชื่อลูกค้า"
                            value="{{ $search }}">
                    </div>
                </div>

                {{-- กรองลูกค้า --}}
                <div class="col-6 col-lg-3">
                    <select name="customer" class="form-select">
                        <option value="">ทุกลูกค้า</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id_customer }}"
                                @selected($customerId === $customer->id_customer)>
                                {{ $customer->name_customer }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- กรองสถานะ --}}
                <div class="col-6 col-lg-2">
                    <select name="status" class="form-select">
                        <option value="">ทุกสถานะ</option>
                        @foreach (\App\Models\SalesOrder::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ปุ่มค้นหา / ล้าง --}}
                <div class="col-6 col-lg-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> ค้นหา
                    </button>

                    @if ($hasFilter)
                        <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>

                {{-- จำนวนรายการต่อหน้า --}}
                <div class="col-12 col-lg-auto ms-lg-auto">
                    @include('partials.per-page', ['perPage' => $perPage])
                </div>
            </div>

            {{-- รีเซ็ตกลับหน้า 1 ทุกครั้งที่ค้นหาใหม่ --}}
            <input type="hidden" name="page" value="1">
        </form>

        {{-- ==================== ปุ่มสร้าง ==================== --}}
        <div class="d-flex justify-content-end align-items-center mb-3">
            <a href="{{ route('sales-orders.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                สร้างใบสั่งขาย
            </a>
        </div>

        {{-- ==================== ตาราง ==================== --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;" class="text-center">ลำดับ</th>
                        <th>เลขที่ใบสั่งขาย</th>
                        <th>ลูกค้า</th>
                        <th>วันที่สั่ง</th>
                        <th>อ้างอิง</th>
                        <th class="text-end">ยอดสุทธิ</th>
                        <th class="text-center">สถานะ</th>
                        <th>แคมป์</th>
                        <th class="text-center" style="width:100px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($salesOrders as $so)
                        <tr>
                            {{-- ลำดับต่อเนื่องข้ามหน้า --}}
                            <td class="text-center text-muted">
                                {{ $salesOrders->firstItem() + $loop->index }}
                            </td>

                            <td><strong>{{ $so->code_so }}</strong></td>

                            <td>{{ $so->customer->name_customer ?? '-' }}</td>

                            <td>{{ $so->order_date?->format('d/m/Y') ?? '-' }}</td>

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

                            <td class="text-center">
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
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                                @if ($hasFilter)
                                    ไม่พบใบสั่งขายที่ตรงกับเงื่อนไขที่ค้นหา
                                    <div class="mt-2">
                                        <a href="{{ route('sales-orders.index') }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            ล้างเงื่อนไขการค้นหา
                                        </a>
                                    </div>
                                @else
                                    ยังไม่มีใบสั่งขาย
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ==================== สรุปจำนวน + ปุ่มเปลี่ยนหน้า ==================== --}}
        @include('partials.pagination-footer', ['paginator' => $salesOrders])

    </div>
@endsection