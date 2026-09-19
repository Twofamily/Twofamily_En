@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขใบสั่งขาย {{ $salesOrder->code_so }}</h3>
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

    @php
        $rows = old('items', $salesOrder->details->map(fn($d) => [
            'id_product' => $d->id_product,
            'quantity'   => (float) $d->quantity,
            'price'      => (float) $d->price_per_unit,
        ])->values()->all());
    @endphp

    <form method="POST" action="{{ route('sales-orders.update', $salesOrder) }}" autocomplete="off">
        @csrf
        @method('PUT')

        <input type="hidden" name="id_quot" value="{{ $salesOrder->id_quot }}">
        <input type="hidden" name="id_customer" value="{{ $salesOrder->id_customer }}">

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">ข้อมูลใบสั่งขาย</h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">ลูกค้า</label>
                        <input type="text" class="form-control bg-light"
                               value="{{ $salesOrder->customer->name_customer ?? '-' }}" readonly>
                        <div class="form-text">เปลี่ยนลูกค้าไม่ได้ เพราะผูกกับใบเสนอราคาต้นทาง</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">อ้างอิงใบเสนอราคา</label>
                        <input type="text" class="form-control bg-light"
                               value="{{ $salesOrder->quotation->code_quot ?? 'สั่งตรง' }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="order_date" class="form-label">วันที่สั่งซื้อ</label>
                        <input type="date" id="order_date" name="order_date"
                               value="{{ old('order_date', $salesOrder->order_date?->toDateString()) }}"
                               class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="due_date" class="form-label">กำหนดส่งมอบ</label>
                        <input type="date" id="due_date" name="due_date"
                               value="{{ old('due_date', $salesOrder->due_date?->toDateString()) }}"
                               class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="po_number" class="form-label">เลขที่ PO ของลูกค้า</label>
                        <input type="text" id="po_number" name="po_number"
                               value="{{ old('po_number', $salesOrder->po_number) }}"
                               class="form-control" maxlength="50">
                    </div>
                    <div class="col-12">
                        <label for="note" class="form-label">หมายเหตุ</label>
                        <textarea id="note" name="note" rows="2"
                                  class="form-control">{{ old('note', $salesOrder->note) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        @include('sales_orders._items', [
            'products'      => $products,
            'rows'          => $rows,
            'discountValue' => $salesOrder->discount,
        ])

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('sales-orders.show', $salesOrder) }}" class="btn btn-outline-secondary">ยกเลิก</a>
            <button class="btn btn-dark">บันทึกการแก้ไข</button>
        </div>
    </form>
</div>
@endsection