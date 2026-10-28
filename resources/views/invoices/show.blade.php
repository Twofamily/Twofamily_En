@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบแจ้งหนี้ {{ $invoice->code_inv }}</h3>
    </div>
@endsection

@php
    // ใบแจ้งหนี้ไม่มีส่วนลด — คิดจากรายการในใบนี้ + VAT
    $subTotal   = $invoice->itemsSubtotal();
    $vat        = $invoice->itemsVat();
    $grandTotal = $invoice->itemsNetTotal();

    // สิทธิ์บันทึก/ลบการชำระ และออกใบเสร็จ
    $canManage = in_array(auth()->user()->role, ['admin', 'staff']);

    $paid      = $invoice->paidAmount();
    // ใบที่ยกเลิกแล้วไม่ใช่หนี้ → ไม่มียอดคงเหลือ
    $remaining = $invoice->isCancelled() ? 0 : $invoice->remainingAmount();

    $showDelete = $canManage && $invoice->canEditPayments();
    $showForm   = $showDelete && $remaining > 0;

    // เงินรับล่วงหน้าของแคมป์ที่หักในใบนี้ได้
    $advanceAvailable = $invoice->availableAdvance();
    $showAdvance      = $showForm && $invoice->canApplyAdvance();
    $advanceMax       = min($remaining, $advanceAvailable);
    $advanceApplied   = $invoice->advanceAppliedAmount();
@endphp

@section('content')
<div class="container py-3">

    {{-- ส่วนหัวเอกสาร (แบบเดียวกับใบเสนอราคา) --}}
    <div class="text-center mb-4">
        <h2>ใบแจ้งหนี้ / ใบวางบิล (Invoice)</h2>
        @include('partials.company-line')
    </div>

    @include('partials.cancelled-banner', ['doc' => $invoice, 'label' => 'ใบแจ้งหนี้'])

    {{-- แถบสถานะการทำงาน --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if ($invoice->isCancelled())
                    <span class="badge bg-dark fs-6">ยกเลิกแล้ว</span>
                @elseif ($invoice->hasReceipt())
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
                @elseif ($invoice->isSettledByAdvance())
                    <span class="text-success">
                        <i class="bi bi-check2-all me-1"></i>
                        ชำระครบด้วยเงินรับล่วงหน้าแล้ว — ใบเสร็จรับเงินล่วงหน้าคือใบเสร็จของยอดนี้ ไม่ต้องออกใหม่
                    </span>
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

                @if ($canManage && ! $invoice->isCancelled())
                    @include('partials.cancel-button', [
                        'action'  => route('invoices.destroy', $invoice->id_invoice),
                        'label'   => 'ยกเลิกใบแจ้งหนี้',
                        'message' => "ยกเลิกใบแจ้งหนี้ {$invoice->code_inv} — ใบส่งของในใบนี้จะกลับไปรอออกใบแจ้งหนี้",
                        'blocker' => $invoice->cancelBlocker(),
                    ])
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

                        <dt class="col-5 text-muted fw-normal">แคมป์</dt>
                        <dd class="col-7">
                            @if ($invoice->camp)
                                <a href="{{ route('camps.show', $invoice->camp) }}" class="text-decoration-none">
                                    {{ $invoice->camp->code_camp }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">วันที่ออก</dt>
                        <dd class="col-7">{{ ($invoice->invoice_date ?? $invoice->created_at)?->format('d/m/Y') ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">ครบกำหนดชำระ</dt>
                        <dd class="col-7">
                            {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}
                            <span class="text-muted">(เครดิต {{ $invoice->credit_days }} วัน)</span>
                            @if ($invoice->isOverdue())
                                <div><span class="badge bg-danger">เลยกำหนด {{ $invoice->daysOverdue() }} วัน</span></div>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ลูกค้ารับบิล</dt>
                        <dd class="col-7">
                            @if ($canManage && ! $invoice->hasReceipt() && ! $invoice->isCancelled())
                                {{-- ใบแจ้งหนี้ทำหน้าที่ใบวางบิล: บันทึกวันที่ลูกค้าเซ็นรับ --}}
                                <form method="POST" action="{{ route('invoices.billReceived', $invoice->id_invoice) }}"
                                      class="d-flex gap-1 align-items-start">
                                    @csrf
                                    @method('PATCH')
                                    <input type="date" name="bill_received_date"
                                           class="form-control form-control-sm @error('bill_received_date') is-invalid @enderror"
                                           value="{{ old('bill_received_date', $invoice->bill_received_date?->format('Y-m-d')) }}"
                                           min="{{ $invoice->invoice_date?->format('Y-m-d') }}"
                                           max="{{ now()->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-sm btn-outline-dark text-nowrap">บันทึก</button>
                                </form>
                                @error('bill_received_date')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            @else
                                {{ $invoice->bill_received_date?->format('d/m/Y') ?? 'ยังไม่ได้บันทึก' }}
                            @endif
                        </dd>

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

                        <dt class="col-5 text-muted fw-normal">ใบส่งของ ({{ $invoice->deliveryNotes->count() }})</dt>
                        <dd class="col-7">
                            @forelse ($invoice->deliveryNotes as $dn)
                                <div>
                                    <a href="{{ route('delivery_notes.show', $dn) }}" class="text-decoration-none">
                                        {{ $dn->code_delivery }}
                                    </a>
                                    <span class="text-muted">· {{ $dn->delivery_date?->format('d/m/Y') }}</span>
                                </div>
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
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

                    @if ($advanceApplied > 0)
                        <div class="alert alert-warning small py-2">
                            หักจากเงินรับล่วงหน้าแล้ว {{ number_format($advanceApplied, 2) }} บาท
                            — ใบเสร็จของใบแจ้งหนี้นี้จะออกเฉพาะยอดที่จ่ายเพิ่ม
                        </div>
                    @endif

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
                                            @if ($p->hasSlip())
                                                <a href="{{ route('payments.slip', $p) }}" target="_blank"
                                                   class="btn btn-sm btn-outline-secondary"
                                                   title="{{ $p->slip_original_name }}">
                                                    <i class="bi {{ $p->isPdfSlip() ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' }} me-1"></i>ดู
                                                </a>
                                            @elseif ($p->method === 'cash')
                                                {{-- เงินสดไม่มีไฟล์: หลักฐานคือผู้รับเงินที่ระบบบันทึก --}}
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-cash me-1"></i>รับเงินสดโดย {{ $p->recordedBy->name ?? '-' }}
                                                </span>
                                            @elseif ($p->advanceReceipt)
                                                {{-- หักเงินล่วงหน้า: หลักฐานอยู่ที่ใบเสร็จรับเงินล่วงหน้า --}}
                                                <a href="{{ route('receipts.show', $p->advanceReceipt->id_receipt) }}"
                                                   class="btn btn-sm btn-outline-warning">
                                                    {{ $p->advanceReceipt->code_rc }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        @if ($showDelete)
                                            <td class="text-center">
                                                <form method="POST" action="{{ route('payments.destroy', $p) }}"
                                                      data-confirm="{{ $p->isAdvanceApplication()
                                                          ? 'รายการหักเงินรับล่วงหน้า ' . number_format($p->amount, 2) . ' บาท จะถูกลบ และเงินล่วงหน้าจะกลับไปให้หักได้อีกครั้ง'
                                                          : 'รายการชำระ ' . number_format($p->amount, 2) . ' บาท และไฟล์หลักฐานจะถูกลบ' }}"
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
                {{-- หักเงินรับล่วงหน้าของแคมป์ (ลูกค้าจ่ายมาก่อนส่งของ) --}}
                @if ($showAdvance)
                    <div class="card border-warning shadow-sm mb-3">
                        <div class="card-body p-4">
                            <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">หักเงินรับล่วงหน้า</h6>

                            <p class="small mb-2">
                                แคมป์ {{ $invoice->camp->code_camp }} มีเงินรับล่วงหน้าคงเหลือ
                                <strong>{{ number_format($advanceAvailable, 2) }}</strong> บาท
                            </p>

                            <form action="{{ route('payments.applyAdvance', $invoice->id_invoice) }}" method="POST"
                                  data-confirm="ระบบจะหักเงินรับล่วงหน้าเข้าใบแจ้งหนี้นี้ ยอดค้างชำระจะลดลงตามยอดที่หัก"
                                  data-confirm-title="ยืนยันการหักเงินรับล่วงหน้า"
                                  data-confirm-ok="หักเงิน"
                                  data-confirm-variant="warning">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">ยอดที่จะหัก (บาท)</label>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                           max="{{ $advanceMax }}" value="{{ old('amount', $advanceMax) }}" required>
                                    <div class="form-text">หักได้สูงสุด {{ number_format($advanceMax, 2) }} บาท</div>
                                </div>
                                <button class="btn btn-warning w-100" type="submit">
                                    <i class="bi bi-piggy-bank me-1"></i> หักเงินรับล่วงหน้า
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card border-0 shadow-sm">
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
                              enctype="multipart/form-data"
            data-confirm="ต้องการบันทึกการชำระเงินนี้หรือไม่ ตรวจสอบยอดเงินและหลักฐานให้ถูกต้องก่อนยืนยัน" data-confirm-title="ยืนยันการบันทึกการชำระเงิน" data-confirm-ok="บันทึก" data-confirm-variant="success">
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
                                <select name="method" id="method" class="form-select" required>
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

                            @include('partials.slip-input', [
                                'requireSlip' => (bool) \App\Models\Setting::get('payment.require_slip', true),
                                'method'      => old('method', 'transfer'),
                            ])

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