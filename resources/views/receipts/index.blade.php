@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบเสร็จ</h3>
    </div>
@endsection

@php
    $hasFilter = $search !== '' || $dateFrom !== null || $dateTo !== null;
    $canManage = in_array(auth()->user()->role, ['admin', 'staff']);
@endphp

@section('content')
    <div class="container py-3">

        {{-- ==================== แถบค้นหา / กรอง / จำนวนต่อหน้า ==================== --}}
        {{-- form เดียวครอบทั้งหมด เพื่อให้ค่าทุกตัวถูกส่งไปพร้อมกัน --}}
        <form method="GET" action="{{ route('receipts.index') }}" class="mb-3">
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
                            placeholder="เลขที่ใบเสร็จ / ใบแจ้งหนี้ / ลูกค้า"
                            value="{{ $search }}">
                    </div>
                </div>

                {{-- ช่วงวันที่ --}}
                <div class="col-6 col-lg-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white small">ตั้งแต่</span>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white small">ถึง</span>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                </div>

                {{-- ปุ่มค้นหา / ล้าง --}}
                <div class="col-6 col-lg-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> ค้นหา
                    </button>

                    @if ($hasFilter)
                        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
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

        {{-- ==================== ตาราง ==================== --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;" class="text-center">ลำดับ</th>
                        <th>เลขที่ใบเสร็จ</th>
                        <th>วันที่ออก</th>
                        <th>ลูกค้า</th>
                        <th>อ้างอิงใบแจ้งหนี้</th>
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

                            <td>{{ \Carbon\Carbon::parse($r->date_receipt)->format('d/m/Y') }}</td>

                            <td>{{ $r->invoice?->customer->name_customer ?? '-' }}</td>

                            <td>
                                @if ($r->invoice)
                                    <a href="{{ route('invoices.show', $r->invoice->id_invoice) }}"
                                       class="text-decoration-none small">
                                        {{ $r->invoice->code_inv }}
                                    </a>
                                @else
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
                                            data-confirm="ใบเสร็จ {{ $r->code_rc }} จะถูกลบออกจากระบบ และรายการชำระของใบแจ้งหนี้จะกลับมาแก้ไขได้อีกครั้ง"
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
                            <td colspan="7" class="text-center text-muted py-4">
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
                            <td colspan="5" class="text-end">
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