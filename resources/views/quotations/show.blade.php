@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบเสนอราคา QT{{ str_pad($quotation->id_quot, 5, '0', STR_PAD_LEFT) }}</h3>
    </div>
@endsection

@php
    $subTotal = $quotation->details->sum('total_price');
    $discount = $quotation->discount ?? 0;
    $grandTotal = max($subTotal - $discount, 0);
@endphp

@section('content')
    <div class="container py-3">
        <div id="quotation">

            <div class="text-center mb-4">
                <h2>ใบเสนอราคา (Quotation)</h2>
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
                    {{ $quotation->customer->name_customer ?? '-' }}
                </p>
                <p>
                    <strong>วันที่ออก:</strong>
                    {{ \Carbon\Carbon::parse($quotation->date_quot)->format('d/m/Y') }}
                </p>

                <p>
                    <strong>สถานะ:</strong>
                    @if ($quotation->status == 'draft')
                        <span class="badge bg-secondary">ร่าง</span>
                    @elseif($quotation->status == 'approved')
                        <span class="badge bg-success">อนุมัติแล้ว</span>
                    @elseif($quotation->status == 'rejected')
                        <span class="badge bg-danger">ยกเลิก</span>
                    @endif
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
                        @foreach ($quotation->details as $i => $d)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $d->product->name_product ?? '-' }}</td>
                                <td class="text-center">{{ $d->quantity }}</td>
                                <td class="text-end">
                                    {{ number_format($d->price_per_unit, 2) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($d->total_price, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">รวมก่อนส่วนลด</th>
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

                <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">
                    ย้อนกลับ
                </a>

                <a href="{{ route('quotation.pdf', $quotation->id_quot) }}" target="_blank" class="btn btn-danger">
                    ดาวน์โหลด PDF
                </a>

                @if ($quotation->status == 'draft')
                    <a href="{{ route('quotations.edit', $quotation->id_quot) }}" class="btn btn-warning">
                        แก้ไข
                    </a>
                @endif

                @if ($quotation->status == 'draft')
                    <form action="{{ route('quotations.approve', $quotation->id_quot) }}" method="POST"
                        style="display:inline;">
                        @csrf
                        <button class="btn btn-success">
                            อนุมัติ
                        </button>
                    </form>
                @endif

                @if ($quotation->status == 'draft')
                    <form method="POST" action="{{ route('quotations.destroy', $quotation) }}"
                        class="d-inline confirm-delete" data-confirm="ข้อมูล {{ $quotation->id_quot }} จะถูกลบออกจากระบบ"
                        data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger" data-confirm-ok="ลบข้อมูล">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                    </form>
                @endif


                @if ($quotation->status == 'approved')
                    <form action="{{ route('invoices.createFromQuotation', $quotation->id_quot) }}" method="POST"
                        style="display:inline;">
                        @csrf
                        <button class="btn btn-primary">
                            สร้างใบแจ้งหนี้
                        </button>
                    </form>
                @endif

            </div>

        </div>
    </div>
@endsection
