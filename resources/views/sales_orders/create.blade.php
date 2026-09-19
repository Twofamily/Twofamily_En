@extends('layout')

@section('namepage')
    <div class="container">
        <h3>สร้างใบสั่งขาย</h3>
    </div>
@endsection

@section('content')
<div class="container py-3">

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">กรุณาตรวจสอบข้อมูล</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ขั้นที่ 1: เลือกใบเสนอราคา --}}
    @unless ($quotation)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">
                    เลือกใบเสนอราคาที่ลูกค้าตกลงสั่งซื้อ
                </h6>

                @if ($quotations->isEmpty())
                    <p class="text-muted mb-0">
                        ยังไม่มีใบเสนอราคาที่พร้อมออกใบสั่งขาย
                        (ต้องอนุมัติแล้ว และยังไม่เคยออกใบสั่งขาย)
                    </p>
                @else
                    <div class="list-group">
                        @foreach ($quotations as $item)
                            <a href="{{ route('sales-orders.create', ['quotation' => $item->id_quot]) }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-semibold">{{ $item->code_quot }}</span>
                                    <span class="text-muted ms-2">{{ $item->customer->name_customer ?? '-' }}</span>
                                    <div class="small text-muted">
                                        ลงวันที่ {{ $item->date_quot?->format('d/m/Y') }}
                                    </div>
                                </div>
                                <span class="fw-semibold">{{ number_format($item->total_amount, 2) }} บาท</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endunless

    {{-- ขั้นที่ 2: กรอกใบสั่งขาย --}}
    @if ($quotation)
        @php
            $rows = old('items', $quotation->details->map(fn($d) => [
                'id_product' => $d->id_product,
                'quantity'   => (float) $d->quantity,
                'price'      => (float) $d->price_per_unit,
            ])->values()->all());

            $discountValue = $quotation->discount;
        @endphp

        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>
                สร้างจากใบเสนอราคา <strong>{{ $quotation->code_quot }}</strong>
                — {{ $quotation->customer->name_customer ?? '-' }}
            </div>
            <a href="{{ route('sales-orders.create') }}" class="btn btn-sm btn-outline-secondary">
                เปลี่ยนใบเสนอราคา
            </a>
        </div>

        <form method="POST" action="{{ route('sales-orders.store') }}" autocomplete="off">
            @csrf
            <input type="hidden" name="id_quot" value="{{ $quotation->id_quot }}">
            <input type="hidden" name="id_customer" value="{{ $quotation->id_customer }}">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">ข้อมูลใบสั่งขาย</h6>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="order_date" class="form-label">วันที่สั่งซื้อ</label>
                            <input type="date" id="order_date" name="order_date"
                                   value="{{ old('order_date', now()->toDateString()) }}"
                                   class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">
                                กำหนดส่งมอบ <span class="text-muted fw-normal">(ไม่บังคับ)</span>
                            </label>
                            <input type="date" id="due_date" name="due_date"
                                   value="{{ old('due_date') }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="po_number" class="form-label">
                                เลขที่ PO ของลูกค้า <span class="text-muted fw-normal">(ไม่บังคับ)</span>
                            </label>
                            <input type="text" id="po_number" name="po_number"
                                   value="{{ old('po_number') }}" class="form-control" maxlength="50">
                        </div>
                        <div class="col-12">
                            <label for="note" class="form-label">หมายเหตุ</label>
                            <textarea id="note" name="note" rows="2" class="form-control">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            @include('sales_orders._items', [
                'products'      => $products,
                'rows'          => $rows,
                'discountValue' => $discountValue,
            ])

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
                <button class="btn btn-dark">บันทึกเป็นร่าง</button>
            </div>
        </form>
    @endif
</div>
@endsection