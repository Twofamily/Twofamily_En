@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบแจ้งหนี้</h3>
    </div>
@endsection

@php
    // มีค่าค้นหา/ตัวกรองใดก็ได้ (ไม่นับเลขหน้า/จำนวนต่อหน้า)
    $hasFilter = collect(request()->except(['page', 'per_page']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

@section('content')
    <div class="container py-3">

        <x-search-bar :action="route('invoices.index')"
                      placeholder="ค้นหาเลขที่ใบแจ้งหนี้ / ลูกค้า / ใบส่งของ / ใบเสนอราคา / สถานะ เช่น ยังไม่ชำระ"
                      :search="$search" :per-page="$perPage"
                      :dates="true" :date-from="$dateFrom" :date-to="$dateTo" date-label="วันที่ออก"
                      :statuses="\App\Models\Invoice::STATUSES + ['cancelled' => ['label' => 'ยกเลิกแล้ว', 'badge' => 'dark']]" :status="$status"
                      status-label="สถานะการชำระ"
                      :ranges="$ranges" />

        @if (auth()->user()->canEdit('invoices'))
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('invoices.create') }}" class="btn btn-dark text-nowrap">
                    <i class="bi bi-plus-lg me-1"></i>ออกใบแจ้งหนี้
                    @if ($pendingNotes > 0)
                        <span class="badge bg-warning text-dark ms-1">ใบส่งของรอออก {{ $pendingNotes }}</span>
                    @endif
                </a>
            </div>
        @endif

        {{-- ==================== ตาราง ==================== --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;" class="text-center">ลำดับ</th>
                        <th>เลขที่ใบแจ้งหนี้</th>
                        <th>วันที่ออก</th>
                        <th>ครบกำหนด</th>
                        <th>ลูกค้า</th>
                        <th>อ้างอิง</th>
                        <th class="text-end">ยอดสุทธิ</th>
                        <th class="text-end">ชำระแล้ว</th>
                        <th class="text-end">คงเหลือ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center" style="width:80px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($invoices as $inv)
                        @php
                            $paid      = (float) ($inv->payments_sum_amount ?? 0);
                            $remaining = max((float) $inv->total - $paid, 0);

                            // ลบได้เฉพาะใบที่ยังไม่มีรายการชำระและยังไม่ออกใบเสร็จ (ตรงกับเงื่อนไขใน Controller)
                            $canDelete = $inv->payments_count === 0 && ! $inv->receipt_exists;
                            $cancelled = $inv->isCancelled();
                            if ($cancelled) { $paid = 0; $remaining = 0; }
                        @endphp

                        <tr @class(['text-muted' => $cancelled])>
                            {{-- ลำดับต่อเนื่องข้ามหน้า --}}
                            <td class="text-center text-muted">
                                {{ $invoices->firstItem() + $loop->index }}
                            </td>

                            <td><strong @class(['text-decoration-line-through' => $cancelled])>{{ $inv->code_inv }}</strong></td>

                            <td>{{ $inv->invoice_date?->format('d/m/Y') ?? '-' }}</td>

                            <td>
                                {{ $inv->due_date?->format('d/m/Y') ?? '-' }}
                                @if ($remaining > 0 && $inv->isOverdue())
                                    <div><span class="badge bg-danger">เลย {{ $inv->daysOverdue() }} วัน</span></div>
                                @endif
                            </td>

                            <td>{{ $inv->customer->name_customer ?? '-' }}</td>

                            <td>
                                {{-- ใบแจ้งหนี้ 1 ใบ รวมใบส่งของได้หลายใบ: โชว์ 2 ใบแรก ที่เหลือบอกจำนวน --}}
                                @foreach ($inv->deliveryNotes->take(2) as $dn)
                                    <a href="{{ route('delivery_notes.show', $dn) }}"
                                       class="text-decoration-none small d-block">
                                        {{ $dn->code_delivery }}
                                    </a>
                                @endforeach
                                @if ($inv->deliveryNotes->count() > 2)
                                    <span class="small text-muted">และอีก {{ $inv->deliveryNotes->count() - 2 }} ใบ</span>
                                @endif
                                @if ($inv->quotation)
                                    <div class="small text-muted">{{ $inv->quotation->code_quot }}</div>
                                @endif
                                @if ($inv->deliveryNotes->isEmpty() && ! $inv->quotation)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td class="text-end">{{ number_format($inv->total, 2) }}</td>

                            <td class="text-end text-success">
                                {{ $paid > 0 ? number_format($paid, 2) : '-' }}
                            </td>

                            <td class="text-end {{ $remaining > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ $remaining > 0 ? number_format($remaining, 2) : '-' }}
                            </td>

                            <td class="text-center">
                                @if ($cancelled)
                                    <span class="badge bg-dark" title="{{ $inv->cancel_reason }}">ยกเลิกแล้ว</span>
                                @else
                                <span class="badge bg-{{ $inv->status_badge }}">{{ $inv->status_label }}</span>
                                @endif
                                @if ($inv->receipt_exists)
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-receipt"></i> ออกใบเสร็จแล้ว
                                    </div>
                                @endif
                            </td>

                            <td class="text-center">
                                {{-- ยกเลิกได้เฉพาะใบที่ยังไม่มีการชำระและยังไม่ออกใบเสร็จ --}}
                                <x-row-actions :show="route('invoices.show', $inv->id_invoice)"
                                               :delete="($canDelete && ! $cancelled) ? route('invoices.destroy', $inv->id_invoice) : null"
                                               :delete-disabled="($canDelete || $cancelled) ? null : 'มีการชำระเงินหรือออกใบเสร็จแล้ว'"
                                               :label="$inv->code_inv"
                                               delete-label="ยกเลิกใบแจ้งหนี้" delete-icon="bi-x-octagon"
                                               delete-title="ยกเลิกใบแจ้งหนี้" delete-ok="ยกเลิกใบแจ้งหนี้"
                                               delete-reason="cancel_reason"
                                               :delete-confirm="'ยกเลิกใบแจ้งหนี้ ' . $inv->code_inv . ' — ใบส่งของในใบนี้จะกลับไปรอออกใบแจ้งหนี้ ยกเลิกแล้วเอกสารยังอยู่ในระบบ (เลขที่ไม่หาย) แต่จะไม่นับในยอดใด ๆ และย้อนกลับไม่ได้'" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                                @if ($hasFilter)
                                    ไม่พบใบแจ้งหนี้ที่ตรงกับเงื่อนไขที่ค้นหา
                                    <div class="mt-2">
                                        <a href="{{ route('invoices.index') }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            ล้างเงื่อนไขการค้นหา
                                        </a>
                                    </div>
                                @else
                                    ยังไม่มีใบแจ้งหนี้
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ==================== สรุปจำนวน + ปุ่มเปลี่ยนหน้า ==================== --}}
        @include('partials.pagination-footer', ['paginator' => $invoices])

    </div>
@endsection