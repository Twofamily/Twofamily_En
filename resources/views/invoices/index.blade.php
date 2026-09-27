@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบแจ้งหนี้</h3>
    </div>
@endsection

@php
    $hasFilter = $search !== '' || $status !== null;
@endphp

@section('content')
    <div class="container py-3">

        {{-- ==================== แถบค้นหา / กรอง / จำนวนต่อหน้า ==================== --}}
        {{-- form เดียวครอบทั้งหมด เพื่อให้ค่าทุกตัวถูกส่งไปพร้อมกัน --}}
        <form method="GET" action="{{ route('invoices.index') }}" class="mb-3">
            <div class="row g-2 align-items-center">

                {{-- ช่องค้นหา --}}
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="เลขที่ / ชื่อลูกค้า / เลขที่ใบส่งของ"
                            value="{{ $search }}">
                    </div>
                </div>

                {{-- กรองสถานะการชำระ --}}
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select">
                        <option value="">ทุกสถานะ</option>
                        @foreach (\App\Models\Invoice::STATUSES as $value => $s)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ปุ่มค้นหา / ล้าง --}}
                <div class="col-6 col-md-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> ค้นหา
                    </button>

                    @if ($hasFilter)
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>

                {{-- จำนวนรายการต่อหน้า --}}
                <div class="col-12 col-md-auto ms-md-auto">
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
                        <th>เลขที่ใบแจ้งหนี้</th>
                        <th>ลูกค้า</th>
                        <th>อ้างอิง</th>
                        <th class="text-end">ยอดสุทธิ</th>
                        <th class="text-end">ชำระแล้ว</th>
                        <th class="text-end">คงเหลือ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($invoices as $inv)
                        @php
                            $paid      = (float) ($inv->payments_sum_amount ?? 0);
                            $remaining = max((float) $inv->total - $paid, 0);

                            // ลบได้เฉพาะใบที่ยังไม่มีรายการชำระและยังไม่ออกใบเสร็จ (ตรงกับเงื่อนไขใน Controller)
                            $canDelete = $inv->payments_count === 0 && ! $inv->receipt_exists;
                        @endphp

                        <tr>
                            {{-- ลำดับต่อเนื่องข้ามหน้า --}}
                            <td class="text-center text-muted">
                                {{ $invoices->firstItem() + $loop->index }}
                            </td>

                            <td><strong>{{ $inv->code_inv }}</strong></td>

                            <td>{{ $inv->customer->name_customer ?? '-' }}</td>

                            <td>
                                @if ($inv->deliveryNote)
                                    <a href="{{ route('delivery-notes.show', $inv->deliveryNote) }}"
                                       class="text-decoration-none small">
                                        {{ $inv->deliveryNote->code_delivery }}
                                    </a>
                                @endif
                                @if ($inv->quotation)
                                    <div class="small text-muted">{{ $inv->quotation->code_quot }}</div>
                                @endif
                                @if (! $inv->deliveryNote && ! $inv->quotation)
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
                                <span class="badge bg-{{ $inv->status_badge }}">{{ $inv->status_label }}</span>
                                @if ($inv->receipt_exists)
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-receipt"></i> ออกใบเสร็จแล้ว
                                    </div>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('invoices.show', $inv->id_invoice) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูใบแจ้งหนี้ {{ $inv->code_inv }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล --}}
                                    @if ($canDelete)
                                        <form method="POST"
                                            action="{{ route('invoices.destroy', $inv->id_invoice) }}"
                                            class="delete-form"
                                            data-confirm="ใบแจ้งหนี้ {{ $inv->code_inv }} จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบข้อมูล"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-outline-danger action-button"
                                                type="submit"
                                                title="ลบข้อมูล"
                                                aria-label="ลบใบแจ้งหนี้ {{ $inv->code_inv }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
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