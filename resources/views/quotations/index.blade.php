@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบเสนอราคา</h3>
    </div>
@endsection

@section('content')
    @php
        // ผู้ใช้สิทธิ์ดูอย่างเดียว: ไม่แสดงปุ่มเพิ่ม / แก้ไข / ลบ (ฝั่ง server ก็กันไว้แล้ว)
        $canEdit = auth()->user()->canEdit('quotations');
    @endphp
    <div class="container py-3">

        <x-search-bar :action="route('quotations.index')"
                      placeholder="ค้นหาเลขที่ใบเสนอราคา / ชื่อลูกค้า / สถานะ เช่น อนุมัติ"
                      :search="$search" :per-page="$perPage"
                      :dates="true" :date-from="$dateFrom" :date-to="$dateTo" date-label="วันที่ออก"
                      :statuses="\App\Models\Quotation::STATUS_LABELS" :status="$status"
                      :ranges="$ranges" />

        {{-- ==================== ปุ่มสร้าง ==================== --}}
        <div class="d-flex justify-content-end align-items-center mb-3">
            @if ($canEdit)
            <a href="{{ route('quotations.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                สร้างใบเสนอราคา
            </a>
            @endif
        </div>

        {{-- ==================== ตาราง ==================== --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;" class="text-center">ลำดับ</th>
                        <th>เลขที่ใบเสนอราคา</th>
                        <th>ลูกค้า</th>
                        <th>วันที่ออก</th>
                        <th class="text-end">ยอดสุทธิ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center" style="width:240px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($quotations as $q)
                        <tr>
                            {{-- ลำดับต่อเนื่องข้ามหน้า เช่น หน้า 2 ที่ 10 ต่อหน้า จะเริ่มที่ 11 --}}
                            <td class="text-center text-muted">
                                {{ $quotations->firstItem() + $loop->index }}
                            </td>

                            <td><strong>{{ $q->code_quot }}</strong></td>

                            <td>{{ $q->customer->name_customer ?? '-' }}</td>

                            <td>{{ \Carbon\Carbon::parse($q->date_quot)->format('d/m/Y') }}</td>

                            <td class="text-end">{{ number_format($q->total_amount, 2) }}</td>

                            <td class="text-center">
                                @if ($q->status == 'draft')
                                    <span class="badge bg-secondary">ร่าง</span>
                                @elseif($q->status == 'approved')
                                    <span class="badge bg-success">อนุมัติแล้ว</span>
                                @elseif($q->status == 'rejected')
                                    <span class="badge bg-danger">ยกเลิก</span>
                                @else
                                    <span class="badge bg-light text-dark">ไม่ทราบสถานะ</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <x-row-actions :show="route('quotations.show', $q)"
                                               :delete="$canEdit ? (route('quotations.destroy', $q)) : null"
                                               :label="$q->code_quot"
                                               :delete-confirm="'ใบเสนอราคา ' . $q->code_quot . ' จะถูกลบออกจากระบบ'">
                                    {{-- อนุมัติ (เฉพาะสถานะร่าง) --}}
                                    @if ($canEdit && $q->status === 'draft')
                                        <x-row-actions.form :action="route('quotations.approve', $q)" method="PATCH"
                                                            icon="bi-check-lg" variant="success"
                                                            :confirm="'ใบเสนอราคา ' . $q->code_quot . ' จะถูกอนุมัติ และสามารถนำไปเปิดแคมป์ได้'"
                                                            confirm-title="ยืนยันการอนุมัติ" confirm-ok="อนุมัติ">
                                            อนุมัติ
                                        </x-row-actions.form>
                                    @endif

                                    {{-- ยกเลิก (สถานะร่างหรืออนุมัติแล้ว) --}}
                                    @if ($canEdit && in_array($q->status, ['draft', 'approved']))
                                        <x-row-actions.form :action="route('quotations.cancel', $q)" method="PATCH"
                                                            icon="bi-slash-circle" variant="warning"
                                                            :confirm="'ใบเสนอราคา ' . $q->code_quot . ' จะถูกยกเลิก'"
                                                            confirm-title="ยืนยันการยกเลิก" confirm-ok="ยกเลิกใบเสนอราคา">
                                            ยกเลิกใบเสนอราคา
                                        </x-row-actions.form>
                                    @endif
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                                @if ($search !== '' || $dateFrom || $dateTo)
                                    ไม่พบใบเสนอราคาที่ตรงกับเงื่อนไขที่ค้นหา
                                    <div class="mt-2">
                                        <a href="{{ route('quotations.index') }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            ล้างเงื่อนไขการค้นหา
                                        </a>
                                    </div>
                                @else
                                    ยังไม่มีใบเสนอราคา
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ==================== สรุปจำนวน + ปุ่มเปลี่ยนหน้า ==================== --}}
        @include('partials.pagination-footer', ['paginator' => $quotations])

    </div>
@endsection