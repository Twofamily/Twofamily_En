@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบแจ้งหนี้ {{ $invoice->code_inv }}</h3>
    </div>
@endsection

@php
    $subTotal = $invoice->quotation->subtotal ?? 0;
    $discount = $invoice->quotation->discount ?? 0;

    $afterDiscount = max($subTotal - $discount, 0);
    $vat = $afterDiscount * 0.07;

    $grandTotal = $afterDiscount + $vat;
@endphp

@section('content')
    <div class="container py-3">
        <div id="invoice">

            <div class="text-center mb-4">
                <h2>ใบแจ้งหนี้ (Invoice)</h2>
                <p>บริษัท Two Family Engineering Co., Ltd.</p>
                <p>
                    โทร: 02-123-4567 |
                    ที่อยู่: 189 หมู่ที่ 14 ตำบลสูงเนิน อำเภอสูงเนิน
                    จ.นครราชสีมา 30170
                </p>
            </div>

            <div class="mb-4">
                <h5>ข้อมูลลูกค้า</h5>
                <p>
                    <strong>ชื่อลูกค้า:</strong>
                    {{ $invoice->customer->name_customer ?? '-' }}
                </p>
                <p>
                    <strong>อ้างอิงใบเสนอราคา:</strong>
                    {{ $invoice->quotation?->code_quot ?? '-' }}
                </p>
                @if ($invoice->deliveryNote)
                    <p>
                        <strong>อ้างอิงใบส่งของ:</strong>
                        {{ $invoice->deliveryNote?->code_dn ?? '-' }}
                    </p>
                @endif
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:5%">ลำดับ</th>
                            <th>รายการ</th>
                            <th class="text-center" style="width:15%">จำนวน</th>
                            <th class="text-end" style="width:20%">ราคาต่อหน่วย</th>
                            <th class="text-end" style="width:20%">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->details as $i => $d)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $d->product->name_product ?? '-' }}</td>
                                <td class="text-center">{{ $d->quantity }}</td>
                                <td class="text-end">
                                    {{ number_format($d->price, 2) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($d->total, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>

                        <tr>
                            <th colspan="4" class="text-end">รวม</th>
                            <th class="text-end">{{ number_format($subTotal, 2) }}</th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">ส่วนลด</th>
                            <th class="text-end">
                                {{ number_format($discount, 2) }}
                            </th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">VAT 7%</th>
                            <th class="text-end">
                                {{ number_format($vat, 2) }}
                            </th>
                        </tr>

                        <tr class="table-primary fw-bold">
                            <th colspan="4" class="text-end">ยอดสุทธิ</th>
                            <th class="text-end">
                                {{ number_format($grandTotal, 2) }}
                            </th>
                        </tr>

                    </tfoot>
                </table>
            </div>

            <div class="text-center mb-5">

                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                    ย้อนกลับ
                </a>

                <a href="{{ route('invoice.pdf', $invoice->id_invoice) }}" target="_blank" class="btn btn-danger">
                    ดาวน์โหลด PDF
                </a>

                @if ($invoice->status == 'unpaid')
                    <form action="{{ route('invoices.pay', $invoice->id_invoice) }}" method="POST"
                        style="display:inline;">
                        @csrf
                        <button class="btn btn-success">
                            ชำระเงินแล้ว
                        </button>
                    </form>
                @endif

                @if ($invoice->status == 'paid')
                    <span class="badge bg-success fs-6">
                        ชำระเงินแล้ว
                    </span>

                    <form action="{{ route('receipts.createFromInvoice', $invoice->id_invoice) }}" method="POST"
                        style="display:inline;">
                        @csrf
                        <button class="btn btn-primary">
                            ออกใบเสร็จ
                        </button>
                    </form>
                @endif

            </div>

        </div>
    </div>
@endsection
