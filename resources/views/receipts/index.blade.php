@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบเสร็จ</h3>
    </div>
@endsection

@php
    // มีค่าค้นหา/ตัวกรองใดก็ได้ (ไม่นับเลขหน้า/จำนวนต่อหน้า)
    $hasFilter = collect(request()->except(['page', 'per_page']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $canManage = in_array(auth()->user()->role, ['admin', 'staff']);
@endphp

@section('content')
    <div class="container py-3">

        {{-- ออกใบเสร็จโดยไม่ต้องมีใบแจ้งหนี้ (ใบเสร็จตามใบแจ้งหนี้ ออกจากหน้าใบแจ้งหนี้) --}}
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center mb-3">
            @if ($canManage)
                <div class="d-flex gap-2">
                    <a href="{{ route('receipts.create', ['type' => 'advance']) }}" class="btn btn-sm btn-outline-dark">
                        <i class="bi bi-cash-coin me-1"></i>รับเงินล่วงหน้า
                    </a>
                    <a href="{{ route('receipts.create', ['type' => 'direct']) }}" class="btn btn-sm btn-dark">
                        <i class="bi bi-cart-check me-1"></i>ขายสด
                    </a>
                </div>
            @endif
        </div>

        <x-search-bar :action="route('receipts.index')"
                      placeholder="ค้นหาเลขที่ใบเสร็จ / ใบแจ้งหนี้ / แคมป์ / ชื่อลูกค้า"
                      :statuses="\App\Models\Receipt::TYPES + ['cancelled' => ['label' => 'ยกเลิกแล้ว', 'badge' => 'dark']]" :status="$type"
                      status-name="type" status-label="ประเภท"
                      :search="$search" :per-page="$perPage"
                      :dates="true" :date-from="$dateFrom" :date-to="$dateTo" date-label="วันที่รับชำระ"
                      :ranges="$ranges" />

        {{-- ==================== ตาราง ==================== --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;" class="text-center">ลำดับ</th>
                        <th>เลขที่ใบเสร็จ</th>
                        <th>ประเภท</th>
                        <th>วันที่ออก</th>
                        <th>ลูกค้า</th>
                        <th>อ้างอิง</th>
                        <th class="text-end">ยอดรับชำระ</th>
                        <th class="text-center" style="width:80px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($receipts as $r)
                        @php $cancelled = $r->isCancelled(); @endphp
                        <tr @class(['text-muted' => $cancelled])>
                            {{-- ลำดับต่อเนื่องข้ามหน้า --}}
                            <td class="text-center text-muted">
                                {{ $receipts->firstItem() + $loop->index }}
                            </td>

                            <td><strong @class(['text-decoration-line-through' => $cancelled])>{{ $r->code_rc }}</strong></td>

                            <td>
                                <span class="badge bg-{{ $r->type_badge }}">{{ $r->type_label }}</span>
                                @if ($cancelled)
                                    <span class="badge bg-dark" title="{{ $r->cancel_reason }}">ยกเลิกแล้ว</span>
                                @endif
                            </td>

                            <td>{{ thai_date($r->date_receipt) }}</td>

                            <td>{{ $r->customer->name_customer ?? '-' }}</td>

                            <td class="small">
                                @if ($r->invoice)
                                    <a href="{{ route('invoices.show', $r->invoice->id_invoice) }}"
                                       class="text-decoration-none">
                                        {{ $r->invoice->code_inv }}
                                    </a>
                                @endif
                                @if ($r->camp)
                                    <div>
                                        <a href="{{ route('camps.show', $r->camp) }}" class="text-decoration-none text-muted">
                                            {{ $r->camp->code_camp }}
                                        </a>
                                    </div>
                                @endif
                                @if (! $r->invoice && ! $r->camp)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td @class(['text-end', 'text-decoration-line-through' => $cancelled])>{{ number_format($r->total, 2) }}</td>

                            <td class="text-center">
                                @php $blocker = ($canManage && ! $cancelled) ? $r->cancelBlocker() : null; @endphp
                                <x-row-actions :show="route('receipts.show', $r->id_receipt)"
                                               :delete="($canManage && ! $cancelled && ! $blocker) ? route('receipts.destroy', $r->id_receipt) : null"
                                               :delete-disabled="$blocker ? 'เงินล่วงหน้าถูกหักใช้แล้ว' : null"
                                               :label="$r->code_rc"
                                               delete-label="ยกเลิกใบเสร็จ" delete-icon="bi-x-octagon"
                                               delete-title="ยกเลิกใบเสร็จ" delete-ok="ยกเลิกใบเสร็จ"
                                               delete-reason="cancel_reason"
                                               :delete-confirm="'ยกเลิกใบเสร็จ ' . $r->code_rc . ($r->isInvoiceType() ? ' — ใบแจ้งหนี้จะกลับมาแก้ไขรายการชำระ / ออกใบเสร็จใหม่ได้ ' : ' — ') . 'ยกเลิกแล้วเอกสารยังอยู่ในระบบ (เลขที่ไม่หาย) แต่จะไม่นับในยอดใด ๆ และย้อนกลับไม่ได้'" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                                @if ($hasFilter)
                                    ไม่พบใบเสร็จที่ตรงกับเงื่อนไขที่ค้นหา
                                    <div class="mt-2">
                                        <a href="{{ route('receipts.index') }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            ล้างเงื่อนไขการค้นหา
                                        </a>
                                    </div>
                                @else
                                    ยังไม่มีรายการใบเสร็จ
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if ($receipts->total() > 0)
                    <tfoot>
                        <tr class="table-primary fw-bold">
                            <td colspan="6" class="text-end">
                                {{ $hasFilter ? 'ยอดรับชำระรวมตามเงื่อนไข' : 'ยอดรับชำระรวมทั้งหมด' }}
                                ({{ number_format($receipts->total()) }} ใบ · ไม่นับใบที่ยกเลิก)
                            </td>
                            <td class="text-end">{{ number_format($sumTotal, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- ==================== สรุปจำนวน + ปุ่มเปลี่ยนหน้า ==================== --}}
        @include('partials.pagination-footer', ['paginator' => $receipts])

    </div>
@endsection