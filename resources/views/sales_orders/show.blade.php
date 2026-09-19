@extends('layout')

@section('namepage')
    <div class="container d-flex justify-content-between align-items-center">
        <h3 class="mb-0">
            ใบสั่งขาย {{ $salesOrder->code_so }}
            <span class="badge bg-{{ $salesOrder->status_color }} align-middle ms-2">
                {{ $salesOrder->status_label }}
            </span>
        </h3>
    </div>
@endsection

@section('content')
<div class="container py-3">

    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- แถบสถานะการทำงาน --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if ($salesOrder->camp)
                    <span class="text-success">
                        <i class="bi bi-check-circle-fill me-1"></i> เปิดแคมป์แล้ว
                    </span>
                    <a href="{{ route('camps.show', $salesOrder->camp->id_camp) }}"
                       class="btn btn-sm btn-outline-dark">
                        {{ $salesOrder->camp->code_camp }} — {{ $salesOrder->camp->name_camp }}
                    </a>
                @elseif ($salesOrder->canOpenCamp())
                    <span class="text-primary">
                        <i class="bi bi-arrow-right-circle me-1"></i> พร้อมเปิดแคมป์งาน
                    </span>
                    <a href="{{ route('camps.create', ['sales_order' => $salesOrder->id_so]) }}"
                       class="btn btn-sm btn-primary">
                        เปิดแคมป์จากใบสั่งขายนี้
                    </a>
                @elseif ($salesOrder->status === 'draft')
                    <span class="text-muted">
                        <i class="bi bi-pencil me-1"></i> ยังเป็นร่าง — ยืนยันก่อนจึงจะเปิดแคมป์ได้
                    </span>
                @elseif ($salesOrder->status === 'cancelled')
                    <span class="text-danger">
                        <i class="bi bi-x-circle me-1"></i> ใบสั่งขายนี้ถูกยกเลิกแล้ว
                    </span>
                @elseif ($salesOrder->status === 'closed')
                    <span class="text-success">
                        <i class="bi bi-check2-all me-1"></i> ปิดงานเรียบร้อยแล้ว
                    </span>
                @endif
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('sales-orders.pdf', $salesOrder->id_so) }}"
                   class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-printer me-1"></i> พิมพ์
                </a>

                @if ($salesOrder->canEdit())
                    <a href="{{ route('sales-orders.edit', $salesOrder->id_so) }}"
                       class="btn btn-sm btn-outline-dark">แก้ไข</a>
                @endif

                @if ($salesOrder->canConfirm())
                    <form method="POST" action="{{ route('sales-orders.confirm', $salesOrder) }}"
                          style="display:inline;"
                          data-confirm="ใบสั่งขาย {{ $salesOrder->code_so }} จะถูกยืนยัน หลังจากนี้จะแก้ไขรายการไม่ได้ และสามารถเปิดแคมป์งานได้"
                          data-confirm-title="ยืนยันใบสั่งขาย"
                          data-confirm-variant="success"
                          data-confirm-ok="ยืนยัน">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-success" type="submit">
                            ยืนยันใบสั่งขาย
                        </button>
                    </form>
                @endif

                @if ($salesOrder->status === 'confirmed' && ! $salesOrder->camp)
                    <form method="POST" action="{{ route('sales-orders.revert', $salesOrder) }}"
                          style="display:inline;"
                          data-confirm="ใบสั่งขาย {{ $salesOrder->code_so }} จะย้อนกลับเป็นร่าง เพื่อให้แก้ไขรายการได้อีกครั้ง"
                          data-confirm-title="ย้อนกลับเป็นร่าง"
                          data-confirm-variant="warning"
                          data-confirm-ok="ย้อนเป็นร่าง">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-warning" type="submit">
                            ย้อนเป็นร่าง
                        </button>
                    </form>
                @endif

                @if ($salesOrder->status === 'in_progress')
                    <form method="POST" action="{{ route('sales-orders.close', $salesOrder) }}"
                          style="display:inline;"
                          data-confirm="ใบสั่งขาย {{ $salesOrder->code_so }} จะถูกปิดงาน"
                          data-confirm-title="ยืนยันการปิดงาน"
                          data-confirm-variant="success"
                          data-confirm-ok="ปิดงาน">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-success" type="submit">
                            ปิดงาน
                        </button>
                    </form>
                @endif

                @if ($salesOrder->canCancel())
                    <form method="POST" action="{{ route('sales-orders.cancel', $salesOrder) }}"
                          style="display:inline;"
                          data-confirm="ใบสั่งขาย {{ $salesOrder->code_so }} จะถูกยกเลิก"
                          data-confirm-title="ยืนยันการยกเลิก"
                          data-confirm-variant="warning"
                          data-confirm-ok="ยกเลิกใบสั่งขาย">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            ยกเลิก
                        </button>
                    </form>
                @endif

                @if ($salesOrder->status === 'draft' && ! $salesOrder->camp)
                    <form method="POST" action="{{ route('sales-orders.destroy', $salesOrder) }}"
                          style="display:inline;"
                          data-confirm="ใบสั่งขาย {{ $salesOrder->code_so }} จะถูกลบออกจากระบบ"
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

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">ข้อมูลทั่วไป</h6>

                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal">ลูกค้า</dt>
                        <dd class="col-7">{{ $salesOrder->customer->name_customer ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">ใบเสนอราคา</dt>
                        <dd class="col-7">
                            @if ($salesOrder->quotation)
                                <a href="{{ route('quotations.show', $salesOrder->quotation->id_quot) }}"
                                   class="text-decoration-none">
                                    {{ $salesOrder->quotation->code_quot }}
                                </a>
                            @else
                                <span class="text-muted">สั่งตรง</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">วันที่สั่งซื้อ</dt>
                        <dd class="col-7">{{ $salesOrder->order_date?->format('d/m/Y') ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">กำหนดส่งมอบ</dt>
                        <dd class="col-7">{{ $salesOrder->due_date?->format('d/m/Y') ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">เลขที่ PO</dt>
                        <dd class="col-7">{{ $salesOrder->po_number ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">แคมป์งาน</dt>
                        <dd class="col-7">
                            @if ($salesOrder->camp)
                                <a href="{{ route('camps.show', $salesOrder->camp->id_camp) }}"
                                   class="text-decoration-none">
                                    {{ $salesOrder->camp->code_camp }}
                                </a>
                            @else
                                <span class="text-muted">ยังไม่ได้เปิด</span>
                            @endif
                        </dd>

                        @if ($salesOrder->note)
                            <dt class="col-5 text-muted fw-normal">หมายเหตุ</dt>
                            <dd class="col-7">{{ $salesOrder->note }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">รายการสินค้าตามสัญญา</h6>

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
                                @forelse ($salesOrder->details as $i => $detail)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>{{ $detail->product->name_product ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($detail->quantity, 2) }}</td>
                                        <td class="text-end">{{ number_format($detail->price_per_unit, 2) }}</td>
                                        <td class="text-end">{{ number_format($detail->total_price, 2) }}</td>
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
                                    <th class="text-end">{{ number_format($salesOrder->subtotal, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end fw-normal text-muted">ส่วนลด</th>
                                    <th class="text-end">{{ number_format($salesOrder->discount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end fw-normal text-muted">ภาษีมูลค่าเพิ่ม 7%</th>
                                    <th class="text-end">{{ number_format($salesOrder->vat_amount, 2) }}</th>
                                </tr>
                                <tr class="table-primary fw-bold">
                                    <th colspan="4" class="text-end">ยอดสุทธิ</th>
                                    <th class="text-end">{{ number_format($salesOrder->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
    </div>
</div>
@endsection