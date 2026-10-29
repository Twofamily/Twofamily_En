@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบสั่งขาย</h3>
    </div>
@endsection

@php
    // มีค่าค้นหา/ตัวกรองใดก็ได้ (ไม่นับเลขหน้า/จำนวนต่อหน้า)
    $hasFilter = collect(request()->except(['page', 'per_page']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

@section('content')
    @php
        // ผู้ใช้สิทธิ์ดูอย่างเดียว: ไม่แสดงปุ่มเพิ่ม / แก้ไข / ลบ (ฝั่ง server ก็กันไว้แล้ว)
        $canEdit = auth()->user()->canEdit('sales_orders');
    @endphp
    <div class="container py-3">

        <x-search-bar :action="route('sales_orders.index')"
                      placeholder="ค้นหาเลขที่ / เลข PO / ลูกค้า / ใบเสนอราคา / แคมป์ / สถานะ"
                      :search="$search" :per-page="$perPage"
                      :dates="true" :date-from="$dateFrom" :date-to="$dateTo" date-label="วันที่สั่ง"
                      :statuses="\App\Models\SalesOrder::STATUS_LABELS" :status="$status"
                      :ranges="$ranges" />

        {{-- ==================== ปุ่มสร้าง ==================== --}}
        <div class="d-flex justify-content-end align-items-center mb-3">
            @if ($canEdit && $canCreate)
                <button type="button" class="btn btn-dark text-nowrap" data-bs-toggle="modal" data-bs-target="#createSalesOrderModal">
                    <i class="bi bi-plus-lg me-1"></i>
                    สร้างใบสั่งขาย
                </button>
            @endif
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
                        <th class="text-center" style="width:80px;">จัดการ</th>
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
                                    @if ($so->status === 'draft')
                                        <div><span class="badge text-bg-warning">รอผู้ดูแลยืนยัน</span></div>
                                    @endif
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
                                <x-row-actions :show="route('sales_orders.show', $so)" :label="$so->code_so" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                                @if ($hasFilter)
                                    ไม่พบใบสั่งขายที่ตรงกับเงื่อนไขที่ค้นหา
                                    <div class="mt-2">
                                        <a href="{{ route('sales_orders.index') }}"
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
    {{-- ==================== หน้าต่างสร้างใบสั่งขาย ==================== --}}
    @if ($canEdit && $canCreate)
        @include('sales_orders._create-modal')

        @if ($errors->createSalesOrder->any() || request('modal') === 'create')
            <script>
                document.addEventListener('DOMContentLoaded', () =>
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('createSalesOrderModal')).show());
            </script>
        @endif
    @endif
@endsection
