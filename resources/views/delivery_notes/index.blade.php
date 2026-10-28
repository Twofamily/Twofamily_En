@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบส่งของ</h3>
    </div>
@endsection

@php
    // มีค่าค้นหา/ตัวกรองใดก็ได้ (ไม่นับเลขหน้า/จำนวนต่อหน้า)
    $hasFilter = collect(request()->except(['page', 'per_page']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

@section('content')
    @php
        // ผู้ใช้สิทธิ์ดูอย่างเดียว: ไม่แสดงปุ่มเพิ่ม / แก้ไข / ลบ (ฝั่ง server ก็กันไว้แล้ว)
        $canEdit = auth()->user()->canEdit('delivery_notes');
    @endphp
    <div class="container py-3">

        <x-search-bar :action="route('delivery_notes.index')"
                      placeholder="ค้นหาเลขที่ใบส่งของ / ลูกค้า / แคมป์ / ใบเสนอราคา / ใบแจ้งหนี้ / สถานะ"
                      :search="$search" :per-page="$perPage"
                      :dates="true" :date-from="$dateFrom" :date-to="$dateTo" date-label="วันที่ส่ง"
                      :statuses="\App\Http\Controllers\DeliveryNoteController::INVOICE_STATUSES" :status="$status" />

        {{-- ==================== ปุ่มสร้าง ==================== --}}
        <div class="d-flex justify-content-end align-items-center mb-3">
            @if ($canEdit)
            <a href="{{ route('delivery_notes.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                สร้างใบส่งของ
            </a>
            @endif
        </div>

        {{-- ==================== ตาราง ==================== --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;" class="text-center">ลำดับ</th>
                        <th>เลขที่ใบส่งของ</th>
                        <th>ลูกค้า</th>
                        <th>แคมป์</th>
                        <th>อ้างอิงใบเสนอราคา</th>
                        <th>วันที่ส่ง</th>
                        <th class="text-center">สถานะใบแจ้งหนี้</th>
                        <th class="text-center" style="width:80px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($deliveryNotes as $deliveryNote)
                        @php $cancelled = $deliveryNote->isCancelled(); @endphp
                        <tr @class(['text-muted' => $cancelled])>
                            {{-- ลำดับต่อเนื่องข้ามหน้า --}}
                            <td class="text-center text-muted">
                                {{ $deliveryNotes->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <strong @class(['text-decoration-line-through' => $cancelled])>{{ $deliveryNote->code_delivery }}</strong>
                            </td>

                            <td>{{ $deliveryNote->customer->name_customer ?? '-' }}</td>

                            <td>
                                @if ($deliveryNote->camp)
                                    <a href="{{ route('camps.show', $deliveryNote->camp->id_camp) }}"
                                       class="text-decoration-none">
                                        {{ $deliveryNote->camp->code_camp }}
                                    </a>
                                    <div class="small text-muted">{{ $deliveryNote->camp->name_camp }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>
                                @if ($deliveryNote->quotation)
                                    <a href="{{ route('quotations.show', $deliveryNote->quotation) }}"
                                       class="text-decoration-none small">
                                        {{ $deliveryNote->quotation->code_quot }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>{{ $deliveryNote->delivery_date?->format('d/m/Y') ?? '-' }}</td>

                            <td class="text-center">
                                @if ($cancelled)
                                    <span class="badge bg-dark" title="{{ $deliveryNote->cancel_reason }}">ยกเลิกแล้ว</span>
                                @elseif ($deliveryNote->invoice)
                                    <span class="badge bg-success">ออกใบแจ้งหนี้แล้ว</span>
                                @else
                                    <span class="badge bg-secondary">รอออกใบแจ้งหนี้</span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{-- ยกเลิกได้เฉพาะใบที่ยังไม่ออกใบแจ้งหนี้ (ไม่มีการลบ เลขที่ต้องอยู่ครบ) --}}
                                @php $blocker = $cancelled ? null : $deliveryNote->cancelBlocker(); @endphp
                                <x-row-actions :show="route('delivery_notes.show', $deliveryNote)"
                                               :delete="$canEdit ? (($cancelled || $blocker) ? null : route('delivery_notes.destroy', $deliveryNote)) : null"
                                               :delete-disabled="$canEdit ? ($cancelled ? null : ($blocker ? 'ออกใบแจ้งหนี้แล้ว' : null)) : null"
                                               :label="$deliveryNote->code_delivery"
                                               delete-label="ยกเลิกใบส่งของ" delete-icon="bi-x-octagon"
                                               delete-title="ยกเลิกใบส่งของ" delete-ok="ยกเลิกใบส่งของ"
                                               delete-reason="cancel_reason"
                                               :delete-confirm="'ยกเลิกใบส่งของ ' . $deliveryNote->code_delivery . ' — ยกเลิกแล้วเอกสารยังอยู่ในระบบ (เลขที่ไม่หาย) แต่จะไม่นับในยอดใด ๆ และย้อนกลับไม่ได้'" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                                @if ($hasFilter)
                                    ไม่พบใบส่งของที่ตรงกับเงื่อนไขที่ค้นหา
                                    <div class="mt-2">
                                        <a href="{{ route('delivery_notes.index') }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            ล้างเงื่อนไขการค้นหา
                                        </a>
                                    </div>
                                @else
                                    ยังไม่มีใบส่งของ
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ==================== สรุปจำนวน + ปุ่มเปลี่ยนหน้า ==================== --}}
        @include('partials.pagination-footer', ['paginator' => $deliveryNotes])

    </div>
@endsection