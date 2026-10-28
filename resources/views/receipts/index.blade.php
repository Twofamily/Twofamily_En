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
                      :statuses="\App\Models\Receipt::TYPES" :status="$type"
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
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($receipts as $r)
                        <tr>
                            {{-- ลำดับต่อเนื่องข้ามหน้า --}}
                            <td class="text-center text-muted">
                                {{ $receipts->firstItem() + $loop->index }}
                            </td>

                            <td><strong>{{ $r->code_rc }}</strong></td>

                            <td><span class="badge bg-{{ $r->type_badge }}">{{ $r->type_label }}</span></td>

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

                            <td class="text-end">{{ number_format($r->total, 2) }}</td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('receipts.show', $r->id_receipt) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูใบเสร็จ {{ $r->code_rc }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล --}}
                                    @if ($canManage)
                                        <form method="POST"
                                            action="{{ route('receipts.destroy', $r->id_receipt) }}"
                                            class="delete-form"
                                            data-confirm="ใบเสร็จ {{ $r->code_rc }} จะถูกลบออกจากระบบ{{ $r->isInvoiceType() ? ' และรายการชำระของใบแจ้งหนี้จะกลับมาแก้ไขได้อีกครั้ง' : ' พร้อมหลักฐานการชำระที่แนบไว้' }}"
                                            data-confirm-title="ยืนยันการลบข้อมูล"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-outline-danger action-button"
                                                type="submit"
                                                title="ลบข้อมูล"
                                                aria-label="ลบใบเสร็จ {{ $r->code_rc }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
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
                                ({{ number_format($receipts->total()) }} ใบ)
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