@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบเสร็จ {{ $receipt->code_rc }}</h3>
    </div>
@endsection

@php
    $inv = $receipt->invoice;

    $subTotal = $inv->quotation->subtotal ?? 0;
    $discount = $inv->quotation->discount ?? 0;

    $afterDiscount = max($subTotal - $discount, 0);
    $vat = $afterDiscount * 0.07;

    $grandTotal = $afterDiscount + $vat;

    $canManage = in_array(auth()->user()->role, ['admin', 'staff']);
@endphp

@section('content')
<div class="container py-3">

    {{-- ส่วนหัวเอกสาร (แบบเดียวกับใบเสนอราคา) --}}
    <div class="text-center mb-4">
        <h2>ใบเสร็จรับเงิน (Receipt)</h2>
        <p>บริษัท Two Family Engineering Co., Ltd.</p>
        <p>
            โทร: 02-123-4567 |
            ที่อยู่: 189 หมู่ที่ 14 ตำบลสูงเนิน อำเภอสูงเนิน
            จ.นครราชสีมา 30170
        </p>
    </div>

    {{-- แถบสถานะการทำงาน --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-success">
                    <i class="bi bi-check2-all me-1"></i> รับชำระเงินครบแล้ว
                </span>
                <a href="{{ route('invoices.show', $inv->id_invoice) }}"
                   class="btn btn-sm btn-outline-dark">
                    {{ $inv->code_inv }}
                </a>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('receipts.pdf', $receipt->id_receipt) }}"
                   class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-printer me-1"></i> พิมพ์
                </a>

                @if ($canManage)
                    <form method="POST" action="{{ route('receipts.destroy', $receipt->id_receipt) }}"
                          style="display:inline;"
                          data-confirm="ใบเสร็จ {{ $receipt->code_rc }} จะถูกลบออกจากระบบ และรายการชำระของใบแจ้งหนี้ {{ $inv->code_inv }} จะกลับมาแก้ไขได้อีกครั้ง"
                          data-confirm-title="ยืนยันการลบข้อมูล"
                          data-confirm-variant="danger"
                          data-confirm-ok="ลบข้อมูล">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            ลบ
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ==================== ข้อมูลทั่วไป + รายการสินค้า ==================== --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">ข้อมูลทั่วไป</h6>

                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal">สถานะ</dt>
                        <dd class="col-7">
                            <span class="badge bg-success">รับเงินแล้ว</span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ลูกค้า</dt>
                        <dd class="col-7">{{ $inv->customer->name_customer ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">วันที่ออก</dt>
                        <dd class="col-7">
                            {{ \Carbon\Carbon::parse($receipt->date_receipt)->format('d/m/Y') }}
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ใบแจ้งหนี้</dt>
                        <dd class="col-7">
                            <a href="{{ route('invoices.show', $inv->id_invoice) }}"
                               class="text-decoration-none">
                                {{ $inv->code_inv }}
                            </a>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ใบส่งของ</dt>
                        <dd class="col-7">
                            @if ($inv->deliveryNote)
                                <a href="{{ route('delivery-notes.show', $inv->deliveryNote) }}"
                                   class="text-decoration-none">
                                    {{ $inv->deliveryNote->code_delivery }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ใบเสนอราคา</dt>
                        <dd class="col-7">
                            @if ($inv->quotation)
                                <a href="{{ route('quotations.show', $inv->quotation->id_quot) }}"
                                   class="text-decoration-none">
                                    {{ $inv->quotation->code_quot }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">รายการสินค้า</h6>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%" class="text-center">ลำดับ</th>
                                    <th>สินค้า</th>
                                    <th class="text-center" style="width:15%">จำนวน</th>
                                    <th class="text-end" style="width:20%">ราคา/หน่วย</th>
                                    <th class="text-end" style="width:20%">รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inv->details as $i => $d)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>{{ $d->product->name_product ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($d->quantity, 2) }}</td>
                                        <td class="text-end">{{ number_format($d->price, 2) }}</td>
                                        <td class="text-end">{{ number_format($d->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            ยังไม่มีรายการสินค้า
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end fw-normal text-muted">รวมเป็นเงิน</th>
                                    <th class="text-end">{{ number_format($subTotal, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end fw-normal text-muted">ส่วนลด</th>
                                    <th class="text-end">{{ number_format($discount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end fw-normal text-muted">ยอดหลังหักส่วนลด</th>
                                    <th class="text-end">{{ number_format($afterDiscount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end fw-normal text-muted">ภาษีมูลค่าเพิ่ม 7%</th>
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
            </div>
        </div>
    </div>

    {{-- ==================== หลักฐานการชำระเงิน ==================== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">หลักฐานการชำระเงิน</h6>

            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width:5%" class="text-center">#</th>
                            <th>วันที่ชำระ</th>
                            <th>วิธีชำระ</th>
                            <th>ธนาคาร / เลขอ้างอิง</th>
                            <th>ผู้บันทึก</th>
                            <th class="text-end">ยอดเงิน</th>
                            <th class="text-center">หลักฐาน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inv->payments as $i => $p)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $p->paid_at->format('d/m/Y') }}</td>
                                <td>{{ $p->method_label }}</td>
                                <td>
                                    {{ $p->bank_name ?? '-' }}
                                    @if ($p->reference_no)
                                        <div class="text-muted">{{ $p->reference_no }}</div>
                                    @endif
                                </td>
                                <td>{{ $p->recordedBy->name ?? '-' }}</td>
                                <td class="text-end fw-semibold">{{ number_format($p->amount, 2) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('payments.slip', $p) }}" target="_blank"
                                       class="btn btn-sm btn-outline-secondary"
                                       title="{{ $p->slip_original_name }}">
                                        <i class="bi {{ $p->isPdfSlip() ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' }} me-1"></i>ดู
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    ไม่มีข้อมูลการชำระเงิน (ใบเสร็จที่ออกก่อนมีระบบแนบหลักฐาน)
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($inv->payments->isNotEmpty())
                        <tfoot>
                            <tr class="table-primary fw-bold">
                                <th colspan="5" class="text-end">รวมรับชำระ</th>
                                <th class="text-end">{{ number_format($inv->paidAmount(), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
    </div>
</div>
@endsection