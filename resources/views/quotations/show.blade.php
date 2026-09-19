@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบเสนอราคา {{ $quotation->code_quot }}</h3>
    </div>
@endsection

@php
    $subTotal = $quotation->details->sum('total_price');
    $discount = $quotation->discount ?? 0;
    $afterDiscount = max($subTotal - $discount, 0);
    $vat = round($afterDiscount * 0.07, 2);
    $grandTotal = $afterDiscount + $vat;
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
                                <td class="text-center">{{ number_format($d->quantity, 2) }}</td>
                                <td class="text-end">{{ number_format($d->price_per_unit, 2) }}</td>
                                <td class="text-end">{{ number_format($d->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">รวมก่อนส่วนลด</th>
                            <th class="text-end">{{ number_format($subTotal, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end">ส่วนลด</th>
                            <th class="text-end">{{ number_format($discount, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end">VAT 7%</th>
                            <th class="text-end">{{ number_format($vat, 2) }}</th>
                        </tr>
                        <tr class="table-primary fw-bold">
                            <th colspan="4" class="text-end">ยอดสุทธิ</th>
                            <th class="text-end">{{ number_format($grandTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>

        {{-- ขั้นถัดไป: ใบสั่งขาย --}}
        @if ($quotation->status === 'approved')
            <div class="border rounded-3 mb-4">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">ใบสั่งขายจากใบเสนอราคานี้</span>

                    @if (!$quotation->salesOrder)
                        <a href="{{ route('sales-orders.create', ['quotation' => $quotation->id_quot]) }}"
                            class="btn btn-sm btn-dark">
                            + ออกใบสั่งขาย
                        </a>
                    @endif
                </div>

                @if ($quotation->salesOrder)
                    @php $so = $quotation->salesOrder; @endphp

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>เลขที่ใบสั่งขาย</th>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th class="text-end">ยอดสุทธิ</th>
                                    <th class="text-center">สถานะ</th>
                                    <th>แคมป์งาน</th>
                                    <th class="text-center" style="width:100px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold">{{ $so->code_so }}</td>
                                    <td>{{ $so->order_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($so->total_amount, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $so->status_color }}">{{ $so->status_label }}</span>
                                    </td>
                                    <td>
                                        @if ($so->camp)
                                            <a href="{{ route('camps.show', $so->camp->id_camp) }}"
                                                class="text-decoration-none">
                                                {{ $so->camp->code_camp }}
                                            </a>
                                            <div class="small text-muted">{{ $so->camp->name_camp }}</div>
                                        @else
                                            <span class="text-muted small">ยังไม่ได้เปิดแคมป์</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('sales-orders.show', $so->id_so) }}"
                                            class="btn btn-sm btn-outline-primary">ดู</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        ยังไม่ได้ออกใบสั่งขายจากใบเสนอราคานี้
                        <div class="small mt-1">
                            เมื่อลูกค้าตกลงสั่งซื้อ กด "ออกใบสั่งขาย" เพื่อเริ่มงาน
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- ปรับแต่งส่วนปุ่มให้ตรงกับใบแจ้งหนี้ -->
        <div class="text-center mb-5">

            <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">
                ย้อนกลับ
            </a>

            <a href="{{ route('quotation.pdf', $quotation->id_quot) }}" target="_blank" class="btn btn-danger">
                ดาวน์โหลด PDF
            </a>

            @if ($quotation->status === 'draft')
                <a href="{{ route('quotations.edit', $quotation->id_quot) }}" class="btn btn-warning">
                    แก้ไข
                </a>

                <form method="POST" action="{{ route('quotations.approve', $quotation) }}" style="display:inline;"
                    data-confirm="ใบเสนอราคา {{ $quotation->code_quot }} จะถูกอนุมัติ และสามารถนำไปเปิดแคมป์ได้"
                    data-confirm-title="ยืนยันการอนุมัติ" data-confirm-variant="success" data-confirm-ok="อนุมัติ">
                    @csrf @method('PATCH')
                    <button class="btn btn-success" type="submit">
                        อนุมัติ
                    </button>
                </form>
            @endif

            @if (in_array($quotation->status, ['draft', 'approved']))
                <form method="POST" action="{{ route('quotations.cancel', $quotation) }}" style="display:inline;"
                    data-confirm="ใบเสนอราคา {{ $quotation->code_quot }} จะถูกยกเลิก" data-confirm-title="ยืนยันการยกเลิก"
                    data-confirm-variant="warning" data-confirm-ok="ยกเลิกใบเสนอราคา">
                    @csrf @method('PATCH')
                    <button class="btn btn-outline-warning" type="submit">
                        ยกเลิก
                    </button>
                </form>
            @endif

            @if ($quotation->status === 'draft')
                <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" style="display:inline;"
                    data-confirm="ใบเสนอราคา {{ $quotation->code_quot }} จะถูกลบออกจากระบบ"
                    data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger" data-confirm-ok="ลบข้อมูล">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">
                        ลบ
                    </button>
                </form>
            @endif

        </div>
    </div>
@endsection
