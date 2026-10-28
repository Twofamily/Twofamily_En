@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบเสร็จ {{ $receipt->code_rc }}</h3>
    </div>
@endsection

@php
    $inv      = $receipt->invoice;
    $customer = $receipt->payer();
    $lines    = $receipt->lines();
    $proofs   = $receipt->proofPayments();

    // ยอดที่บันทึกไว้ในใบเสร็จ (ใบเก่าที่ยังไม่มีคอลัมน์ ถอยไปใช้ยอดของใบแจ้งหนี้)
    $subTotal = (float) $receipt->subtotal ?: ($inv?->itemsSubtotal() ?? 0);
    $vat      = (float) $receipt->vat_amount ?: ($inv?->itemsVat() ?? 0);
    $gross    = round($subTotal + $vat, 2);
    $deducted = (float) $receipt->advance_deducted;

    $canManage = in_array(auth()->user()->role, ['admin', 'staff']);

    $cancelMessage = $receipt->isInvoiceType()
        ? "ยกเลิกใบเสร็จ {$receipt->code_rc} — ใบแจ้งหนี้ " . ($inv->code_inv ?? '') . ' จะกลับมาแก้ไขรายการชำระ / ออกใบเสร็จใหม่ได้'
        : "ยกเลิกใบเสร็จ {$receipt->code_rc} — หลักฐานการชำระยังเก็บไว้ แต่ไม่นับเป็นเงินรับ";
@endphp

@section('content')
<div class="container py-3">

    {{-- ส่วนหัวเอกสาร (แบบเดียวกับใบเสนอราคา) --}}
    <div class="text-center mb-4">
        <h2>ใบเสร็จรับเงิน (Receipt)</h2>
        @include('partials.company-line')
    </div>

    @include('partials.cancelled-banner', ['doc' => $receipt, 'label' => 'ใบเสร็จ'])

    {{-- แถบสถานะการทำงาน --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-{{ $receipt->type_badge }} fs-6">{{ $receipt->type_label }}</span>

                @if ($inv)
                    <a href="{{ route('invoices.show', $inv->id_invoice) }}" class="btn btn-sm btn-outline-dark">
                        {{ $inv->code_inv }}
                    </a>
                @endif
                @if ($receipt->camp)
                    <a href="{{ route('camps.show', $receipt->camp) }}" class="btn btn-sm btn-outline-dark">
                        {{ $receipt->camp->code_camp }} — {{ $receipt->camp->name_camp }}
                    </a>
                @endif

                @if ($receipt->isCancelled())
                    <span class="badge bg-dark fs-6">ยกเลิกแล้ว</span>
                @elseif ($receipt->isAdvance())
                    <span class="{{ $receipt->advanceRemaining() > 0 ? 'text-warning' : 'text-success' }}">
                        <i class="bi bi-piggy-bank me-1"></i>
                        หักไปแล้ว {{ number_format($receipt->advanceUsed(), 2) }}
                        · คงเหลือ {{ number_format($receipt->advanceRemaining(), 2) }} บาท
                    </span>
                @endif
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('receipts.pdf', $receipt->id_receipt) }}"
                   class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-printer me-1"></i> พิมพ์
                </a>

                @if ($canManage && ! $receipt->isCancelled())
                    @include('partials.cancel-button', [
                        'action'  => route('receipts.destroy', $receipt->id_receipt),
                        'label'   => 'ยกเลิกใบเสร็จ',
                        'message' => $cancelMessage,
                        'blocker' => $receipt->cancelBlocker(),
                    ])
                @endif
            </div>
        </div>
    </div>

    {{-- ==================== ข้อมูลทั่วไป + รายการ ==================== --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">ข้อมูลทั่วไป</h6>

                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal">ประเภท</dt>
                        <dd class="col-7">{{ $receipt->type_label }}</dd>

                        <dt class="col-5 text-muted fw-normal">ลูกค้า</dt>
                        <dd class="col-7">{{ $customer->name_customer ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">วันที่รับเงิน</dt>
                        <dd class="col-7">{{ thai_date($receipt->date_receipt) }}</dd>

                        <dt class="col-5 text-muted fw-normal">แคมป์</dt>
                        <dd class="col-7">
                            @if ($receipt->camp)
                                <a href="{{ route('camps.show', $receipt->camp) }}" class="text-decoration-none">
                                    {{ $receipt->camp->code_camp }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        @if ($inv)
                            <dt class="col-5 text-muted fw-normal">ใบแจ้งหนี้</dt>
                            <dd class="col-7">
                                <a href="{{ route('invoices.show', $inv->id_invoice) }}" class="text-decoration-none">
                                    {{ $inv->code_inv }}
                                </a>
                            </dd>

                            <dt class="col-5 text-muted fw-normal">ใบส่งของ</dt>
                            <dd class="col-7">
                                @forelse ($inv->deliveryNotes as $dn)
                                    <a href="{{ route('delivery_notes.show', $dn) }}" class="text-decoration-none d-block">
                                        {{ $dn->code_delivery }}
                                    </a>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </dd>
                        @endif

                        <dt class="col-5 text-muted fw-normal">ผู้ออกเอกสาร</dt>
                        <dd class="col-7">{{ $receipt->issuer->name ?? '-' }}</dd>

                        @if ($receipt->note)
                            <dt class="col-5 text-muted fw-normal">หมายเหตุ</dt>
                            <dd class="col-7">{{ $receipt->note }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">รายการ</h6>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%" class="text-center">ลำดับ</th>
                                    <th>รายการ</th>
                                    <th class="text-center" style="width:15%">จำนวน</th>
                                    <th class="text-end" style="width:20%">ราคา/หน่วย</th>
                                    <th class="text-end" style="width:20%">รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lines as $i => $line)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>{{ $line['name'] }}</td>
                                        <td class="text-center">{{ number_format($line['quantity'], 2) }}</td>
                                        <td class="text-end">{{ number_format($line['price'], 2) }}</td>
                                        <td class="text-end">{{ number_format($line['total'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">ไม่มีรายการ</td>
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
                                @if ($deducted > 0)
                                    <tr>
                                        <th colspan="4" class="text-end fw-normal text-muted">รวมทั้งสิ้น</th>
                                        <th class="text-end">{{ number_format($gross, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end fw-normal text-muted">หัก เงินรับล่วงหน้า</th>
                                        <th class="text-end text-danger">-{{ number_format($deducted, 2) }}</th>
                                    </tr>
                                @endif
                                <tr class="table-primary fw-bold">
                                    <th colspan="4" class="text-end">จำนวนเงินที่ได้รับ</th>
                                    <th class="text-end">{{ number_format($receipt->total, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== หลักฐานการชำระเงิน ==================== --}}
    <div class="card border-0 shadow-sm mb-3">
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
                        @forelse ($proofs as $i => $p)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $p->paid_at->format('d/m/Y') }}</td>
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
                                <td>{{ $p->recordedBy->name ?? '-' }}</td>
                                <td class="text-end fw-semibold">{{ number_format($p->amount, 2) }}</td>
                                <td class="text-center">
                                    @if ($p->hasSlip())
                                        <a href="{{ route('payments.slip', $p) }}" target="_blank"
                                           class="btn btn-sm btn-outline-secondary"
                                           title="{{ $p->slip_original_name }}">
                                            <i class="bi {{ $p->isPdfSlip() ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' }} me-1"></i>ดู
                                        </a>
                                    @elseif ($p->method === 'cash')
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-cash me-1"></i>รับเงินสดโดย {{ $p->recordedBy->name ?? '-' }}
                                        </span>
                                    @else
                                        <span class="text-muted">ไม่มีไฟล์</span>
                                    @endif
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
                    @if ($proofs->isNotEmpty())
                        <tfoot>
                            <tr class="table-primary fw-bold">
                                <th colspan="5" class="text-end">รวมรับชำระ</th>
                                <th class="text-end">{{ number_format($proofs->sum(fn ($p) => (float) $p->amount), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- ==================== เงินล่วงหน้าใบนี้ถูกหักไปที่ไหน ==================== --}}
    @if ($receipt->isAdvance())
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">การหักเงินรับล่วงหน้าในใบแจ้งหนี้</h6>

                <div class="table-responsive">
                    <table class="table align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>วันที่หัก</th>
                                <th>ใบแจ้งหนี้</th>
                                <th class="text-end">ยอดที่หัก</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($receipt->appliedAdvances as $use)
                                <tr>
                                    <td>{{ $use->paid_at->format('d/m/Y') }}</td>
                                    <td>
                                        @if ($use->invoice)
                                            <a href="{{ route('invoices.show', $use->invoice->id_invoice) }}" class="text-decoration-none">
                                                {{ $use->invoice->code_inv }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($use->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        ยังไม่ถูกหัก — ไปที่ใบแจ้งหนี้ของแคมป์นี้ แล้วกด "หักเงินรับล่วงหน้า"
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <th colspan="2" class="text-end">คงเหลือให้หัก</th>
                                <th class="text-end">{{ number_format($receipt->advanceRemaining(), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
    </div>
</div>
@endsection
