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

    // สิทธิ์บันทึก/ลบการชำระ และออกใบเสร็จ
    $canManage = in_array(auth()->user()->role, ['admin', 'staff']);

    $paid      = $invoice->paidAmount();
    $remaining = $invoice->remainingAmount();

    $showDelete = $canManage && $invoice->canEditPayments();
    $showForm   = $showDelete && $remaining > 0;
@endphp

@section('content')
<div class="container py-3">

    {{-- ส่วนหัวเอกสาร (แบบเดียวกับใบเสนอราคา) --}}
    <div class="text-center mb-4">
        <h2>ใบแจ้งหนี้ (Invoice)</h2>
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
                @if ($invoice->hasReceipt())
                    <span class="text-success">
                        <i class="bi bi-check2-all me-1"></i> ออกใบเสร็จแล้ว
                    </span>
                    <a href="{{ route('receipts.show', $invoice->receipt->id_receipt) }}"
                       class="btn btn-sm btn-outline-dark">
                        {{ $invoice->receipt->code_rc }}
                    </a>
                @elseif ($invoice->canIssueReceipt())
                    <span class="text-primary">
                        <i class="bi bi-arrow-right-circle me-1"></i> ชำระครบแล้ว พร้อมออกใบเสร็จ
                    </span>
                    @if ($canManage)
                        <form method="POST" action="{{ route('receipts.createFromInvoice', $invoice->id_invoice) }}"
                              style="display:inline;"
                              data-confirm="ระบบจะออกใบเสร็จจากใบแจ้งหนี้ {{ $invoice->code_inv }} หลังจากนี้จะแก้ไขรายการชำระไม่ได้"
                              data-confirm-title="ยืนยันการออกใบเสร็จ"
                              data-confirm-variant="success"
                              data-confirm-ok="ออกใบเสร็จ">
                            @csrf
                            <button class="btn btn-sm btn-primary" type="submit">
                                ออกใบเสร็จจากใบแจ้งหนี้นี้
                            </button>
                        </form>
                    @endif
                @elseif ($paid > 0)
                    <span class="text-warning">
                        <i class="bi bi-hourglass-split me-1"></i>
                        ชำระบางส่วน — คงเหลือ {{ number_format($remaining, 2) }} บาท
                    </span>
                @else
                    <span class="text-muted">
                        <i class="bi bi-hourglass me-1"></i>
                        รอชำระเงิน — แนบหลักฐานให้ครบยอดก่อนจึงจะออกใบเสร็จได้
                    </span>
                @endif
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('invoice.pdf', $invoice->id_invoice) }}"
                   class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-printer me-1"></i> พิมพ์
                </a>

                @if ($canManage && ! $invoice->hasPayments() && ! $invoice->hasReceipt())
                    <form method="POST" action="{{ route('invoices.destroy', $invoice->id_invoice) }}"
                          style="display:inline;"
                          data-confirm="ใบแจ้งหนี้ {{ $invoice->code_inv }} จะถูกลบออกจากระบบ"
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
                            <span class="badge bg-{{ $invoice->status_badge }}">
                                {{ $invoice->status_label }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ลูกค้า</dt>
                        <dd class="col-7">{{ $invoice->customer->name_customer ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">วันที่ออก</dt>
                        <dd class="col-7">{{ $invoice->created_at?->format('d/m/Y') ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">ใบเสนอราคา</dt>
                        <dd class="col-7">
                            @if ($invoice->quotation)
                                <a href="{{ route('quotations.show', $invoice->quotation->id_quot) }}"
                                   class="text-decoration-none">
                                    {{ $invoice->quotation->code_quot }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ใบส่งของ</dt>
                        <dd class="col-7">
                            @if ($invoice->deliveryNote)
                                <a href="{{ route('delivery-notes.show', $invoice->deliveryNote) }}"
                                   class="text-decoration-none">
                                    {{ $invoice->deliveryNote->code_delivery }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ใบเสร็จ</dt>
                        <dd class="col-7">
                            @if ($invoice->hasReceipt())
                                <a href="{{ route('receipts.show', $invoice->receipt->id_receipt) }}"
                                   class="text-decoration-none">
                                    {{ $invoice->receipt->code_rc }}
                                </a>
                            @else
                                <span class="text-muted">ยังไม่ได้ออก</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">รายการสินค้าที่เรียกเก็บ</h6>

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
                                @forelse ($invoice->details as $i => $d)
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

    {{-- ==================== การชำระเงิน ==================== --}}
    <div class="row g-3">

        {{-- ประวัติการชำระ --}}
        <div class="{{ $showForm ? 'col-lg-8' : 'col-12' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">การชำระเงิน</h6>

                    {{-- สรุปยอด --}}
                    <div class="row g-2 text-center mb-3">
                        <div class="col-4">
                            <div class="bg-light rounded-3 p-2">
                                <div class="small text-muted">ยอดที่ต้องชำระ</div>
                                <div class="fw-bold">{{ number_format($invoice->total, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded-3 p-2">
                                <div class="small text-muted">ชำระแล้ว</div>
                                <div class="fw-bold text-success">{{ number_format($paid, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded-3 p-2">
                                <div class="small text-muted">คงเหลือ</div>
                                <div class="fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($remaining, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- รายการชำระ --}}
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%" class="text-center">#</th>
                                    <th>วันที่ชำระ</th>
                                    <th>วิธีชำระ</th>
                                    <th>ธนาคาร / เลขอ้างอิง</th>
                                    <th class="text-end">ยอดเงิน</th>
                                    <th class="text-center">หลักฐาน</th>
                                    @if ($showDelete)
                                        <th style="width:5%"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoice->payments as $i => $p)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            {{ $p->paid_at->format('d/m/Y') }}
                                            <div class="text-muted">โดย {{ $p->recordedBy->name ?? '-' }}</div>
                                        </td>
                                        <td>{{ $p->method_label }}</td>
                                        <td>
                                            {{ $p->bank_name ?? '-' }}
                                            @if ($p->reference_no)
                                                <div class="text-muted">{{ $p->reference_no }}</div>
                                            @endif
                                            @if ($p->note)
                                                <div class="text-muted fst-italic">{{ $p->note }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format($p->amount, 2) }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('payments.slip', $p) }}" target="_blank"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="{{ $p->slip_original_name }}">
                                                <i class="bi {{ $p->isPdfSlip() ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' }} me-1"></i>ดู
                                            </a>
                                        </td>
                                        @if ($showDelete)
                                            <td class="text-center">
                                                <form method="POST" action="{{ route('payments.destroy', $p) }}"
                                                      data-confirm="รายการชำระ {{ number_format($p->amount, 2) }} บาท และไฟล์หลักฐานจะถูกลบ"
                                                      data-confirm-title="ยืนยันการลบรายการชำระ"
                                                      data-confirm-variant="danger"
                                                      data-confirm-ok="ลบข้อมูล">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="ลบ">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $showDelete ? 7 : 6 }}" class="text-center text-muted py-4">
                                            ยังไม่มีการบันทึกการชำระเงิน
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ฟอร์มแนบหลักฐาน: แสดงเมื่อมีสิทธิ์ + ยังไม่ออกใบเสร็จ + ยังมียอดค้าง --}}
        @if ($showForm)
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">บันทึกการชำระเงิน</h6>

                        @if ($errors->any())
                            <div class="alert alert-danger small">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('payments.store', $invoice->id_invoice) }}" method="POST"
                              enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">วันที่ชำระ <span class="text-danger">*</span></label>
                                <input type="date" name="paid_at" class="form-control"
                                       value="{{ old('paid_at', now()->format('Y-m-d')) }}"
                                       max="{{ now()->format('Y-m-d') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ยอดเงิน (บาท) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                       max="{{ $remaining }}" value="{{ old('amount', $remaining) }}" required>
                                <div class="form-text">คงเหลือ {{ number_format($remaining, 2) }} บาท</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">วิธีชำระ <span class="text-danger">*</span></label>
                                <select name="method" class="form-select" required>
                                    @foreach (\App\Models\Payment::METHODS as $key => $label)
                                        <option value="{{ $key }}" @selected(old('method', 'transfer') === $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ธนาคาร <small class="text-muted">(กรณีโอนเงิน)</small></label>
                                <input type="text" name="bank_name" class="form-control" maxlength="100"
                                       value="{{ old('bank_name') }}" placeholder="เช่น กสิกรไทย">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">เลขอ้างอิง / เลขเช็ค</label>
                                <input type="text" name="reference_no" class="form-control" maxlength="50"
                                       value="{{ old('reference_no') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">หลักฐานการชำระ <span class="text-danger">*</span></label>
                                <input type="file" name="slip" class="form-control"
                                       accept=".jpg,.jpeg,.png,.pdf" required>
                                <div class="form-text">
                                    สลิปโอน / รูปเช็ค / ใบรับเงินสดที่ลูกค้าเซ็น — JPG, PNG หรือ PDF ไม่เกิน 5 MB
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">หมายเหตุ</label>
                                <textarea name="note" class="form-control" rows="2" maxlength="500">{{ old('note') }}</textarea>
                            </div>

                            <button class="btn btn-dark w-100" type="submit">
                                <i class="bi bi-upload me-1"></i> บันทึกการชำระเงิน
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-3">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
    </div>
</div>
@endsection