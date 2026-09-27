@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบส่งของ</h3>
    </div>
@endsection

@php
    $hasFilter = $search !== '' || $status !== null;
@endphp

@section('content')
    <div class="container py-3">

        {{-- ==================== แถบค้นหา / กรอง / จำนวนต่อหน้า ==================== --}}
        {{-- form เดียวครอบทั้งหมด เพื่อให้ค่าทุกตัวถูกส่งไปพร้อมกัน --}}
        <form method="GET" action="{{ route('delivery-notes.index') }}" class="mb-3">
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
                            placeholder="เลขที่ / ชื่อลูกค้า / แคมป์"
                            value="{{ $search }}">
                    </div>
                </div>

                {{-- กรองสถานะใบแจ้งหนี้ --}}
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select">
                        <option value="">ทุกสถานะ</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ปุ่มค้นหา / ล้าง --}}
                <div class="col-6 col-md-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> ค้นหา
                    </button>

                    @if ($hasFilter)
                        <a href="{{ route('delivery-notes.index') }}" class="btn btn-outline-secondary">
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

        {{-- ==================== ปุ่มสร้าง ==================== --}}
        <div class="d-flex justify-content-end align-items-center mb-3">
            <a href="{{ route('delivery-notes.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                สร้างใบส่งของ
            </a>
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
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($deliveryNotes as $deliveryNote)
                        <tr>
                            {{-- ลำดับต่อเนื่องข้ามหน้า --}}
                            <td class="text-center text-muted">
                                {{ $deliveryNotes->firstItem() + $loop->index }}
                            </td>

                            <td><strong>{{ $deliveryNote->code_delivery }}</strong></td>

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
                                @if ($deliveryNote->invoice)
                                    <span class="badge bg-success">ออกใบแจ้งหนี้แล้ว</span>
                                @else
                                    <span class="badge bg-secondary">รอออกใบแจ้งหนี้</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('delivery-notes.show', $deliveryNote) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูใบส่งของ {{ $deliveryNote->code_delivery }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล (เฉพาะที่ยังไม่ออกใบแจ้งหนี้) --}}
                                    @if (! $deliveryNote->invoice)
                                        <form method="POST"
                                            action="{{ route('delivery-notes.destroy', $deliveryNote) }}"
                                            class="delete-form"
                                            data-confirm="ใบส่งของ {{ $deliveryNote->code_delivery }} จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบข้อมูล"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-outline-danger action-button"
                                                type="submit"
                                                title="ลบข้อมูล"
                                                aria-label="ลบใบส่งของ {{ $deliveryNote->code_delivery }}">
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
                                    ไม่พบใบส่งของที่ตรงกับเงื่อนไขที่ค้นหา
                                    <div class="mt-2">
                                        <a href="{{ route('delivery-notes.index') }}"
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