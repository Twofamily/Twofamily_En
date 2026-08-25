@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบเสร็จ RC{{ str_pad($receipt->id_receipt, 5, '0', STR_PAD_LEFT) }}</h3>
    </div>
@endsection

@php
    $inv = $receipt->invoice;

    $subTotal = $inv->quotation->subtotal ?? 0;
    $discount = $inv->quotation->discount ?? 0;

    $afterDiscount = max($subTotal - $discount, 0);
    $vat = $afterDiscount * 0.07;

    $grandTotal = $afterDiscount + $vat;
@endphp

@section('content')
    <div class="container py-3">
        <div id="receipt">

            <div class="text-center mb-4">
                <h2>ใบเสร็จรับเงิน (Receipt)</h2>
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
                    {{ $inv->customer->name_customer ?? '-' }}
                </p>

                <p>
                    <strong>อ้างอิงใบแจ้งหนี้:</strong>
                    INV{{ str_pad($inv->id_invoice, 5, '0', STR_PAD_LEFT) }}
                </p>

                <p>
                    <strong>วันที่:</strong>
                    {{ \Carbon\Carbon::parse($receipt->date_receipt)->format('d/m/Y') }}
                </p>
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
                        @foreach ($inv->details as $i => $d)
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
                            <th class="text-end">
                                {{ number_format($subTotal, 2) }}
                            </th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">ส่วนลด</th>
                            <th class="text-end">
                                {{ number_format($discount, 2) }}
                            </th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">ยอดหลังหักส่วนลด</th>
                            <th class="text-end">
                                {{ number_format($afterDiscount, 2) }}
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

                <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
                    ย้อนกลับ
                </a>

                <a href="{{ route('receipts.pdf', $receipt->id_receipt) }}" target="_blank" class="btn btn-danger">
                    ดาวน์โหลด PDF
                </a>

                <form method="POST" action="{{ route('receipts.destroy', $receipt) }}" class="d-inline"
                    data-confirm="ข้อมูล {{ $receipt->id_receipt }} จะถูกลบออกจากระบบ" 
                    data-confirm-title="ยืนยันการลบข้อมูล"
                    data-confirm-variant="danger" 
                    data-confirm-ok="ลบข้อมูล">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                </form>

            </div>

        </div>
    </div>
@endsection
