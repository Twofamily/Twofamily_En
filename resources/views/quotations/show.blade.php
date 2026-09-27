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

    $so = $quotation->salesOrder;

    $statusMap = [
        'draft'    => ['label' => 'ร่าง',        'badge' => 'secondary'],
        'approved' => ['label' => 'อนุมัติแล้ว', 'badge' => 'success'],
        'rejected' => ['label' => 'ยกเลิก',      'badge' => 'danger'],
    ];
    $status = $statusMap[$quotation->status] ?? ['label' => $quotation->status, 'badge' => 'secondary'];
@endphp

@section('content')
    <div class="container py-3">

        {{-- ==================== ส่วนหัว (ภาพที่ 1) ==================== --}}
        <div class="text-center mb-4">
            <h2>ใบเสนอราคา (Quotation)</h2>
            <p>บริษัท Two Family Engineering Co., Ltd.</p>
            <p>
                โทร: 02-123-4567 |
                ที่อยู่: 189 หมู่ที่ 14 ตำบลสูงเนิน อำเภอสูงเนิน
                จ.นครราชสีมา 30170
            </p>
        </div>

        {{-- ==================== แถบสถานะ + ปุ่มจัดการ ==================== --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">

                {{-- ซ้าย: สถานะ + ขั้นตอนถัดไป --}}
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if ($quotation->status === 'draft')
                        <span class="text-secondary fw-semibold">
                            <i class="bi bi-pencil-square me-2"></i>ฉบับร่าง รอตรวจสอบและอนุมัติ
                        </span>
                        <form method="POST" action="{{ route('quotations.approve', $quotation) }}" class="d-inline"
                            data-confirm="ใบเสนอราคา {{ $quotation->code_quot }} จะถูกอนุมัติ และสามารถนำไปเปิดแคมป์ได้"
                            data-confirm-title="ยืนยันการอนุมัติ" data-confirm-variant="success" data-confirm-ok="อนุมัติ">
                            @csrf @method('PATCH')
                            <button class="btn btn-success" type="submit">อนุมัติ</button>
                        </form>

                    @elseif ($quotation->status === 'approved' && !$so)
                        <span class="text-primary fw-semibold">
                            <i class="bi bi-arrow-right-circle me-2"></i>พร้อมออกใบสั่งขาย
                        </span>
                        <a href="{{ route('sales-orders.create', ['quotation' => $quotation->id_quot]) }}"
                            class="btn btn-primary">
                            ออกใบสั่งขายจากใบเสนอราคานี้
                        </a>

                    @elseif ($quotation->status === 'approved' && $so)
                        <span class="text-success fw-semibold">
                            <i class="bi bi-check-circle me-2"></i>ออกใบสั่งขายแล้ว
                        </span>
                        <a href="{{ route('sales-orders.show', $so->id_so) }}" class="btn btn-outline-primary">
                            {{ $so->code_so }}
                        </a>

                    @elseif ($quotation->status === 'rejected')
                        <span class="text-danger fw-semibold">
                            <i class="bi bi-x-circle me-2"></i>ใบเสนอราคานี้ถูกยกเลิกแล้ว
                        </span>
                    @endif
                </div>

                {{-- ขวา: ปุ่มจัดการ --}}
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('quotation.pdf', $quotation->id_quot) }}" target="_blank"
                        class="btn btn-outline-secondary">
                        <i class="bi bi-printer me-1"></i>พิมพ์
                    </a>

                    @if ($quotation->status === 'draft')
                        <a href="{{ route('quotations.edit', $quotation->id_quot) }}" class="btn btn-outline-warning">
                            แก้ไข
                        </a>
                    @endif

                    @if (in_array($quotation->status, ['draft', 'approved']))
                        <form method="POST" action="{{ route('quotations.cancel', $quotation) }}" class="d-inline"
                            data-confirm="ใบเสนอราคา {{ $quotation->code_quot }} จะถูกยกเลิก"
                            data-confirm-title="ยืนยันการยกเลิก" data-confirm-variant="warning"
                            data-confirm-ok="ยกเลิกใบเสนอราคา">
                            @csrf @method('PATCH')
                            <button class="btn btn-outline-danger" type="submit">ยกเลิก</button>
                        </form>
                    @endif

                    @if ($quotation->status === 'draft')
                        <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" class="d-inline"
                            data-confirm="ใบเสนอราคา {{ $quotation->code_quot }} จะถูกลบออกจากระบบ"
                            data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger"
                            data-confirm-ok="ลบข้อมูล">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger" type="submit">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        </div>

        {{-- ==================== เนื้อหา 2 คอลัมน์ (ภาพที่ 2) ==================== --}}
        <div class="row g-4 mb-4">

            {{-- ซ้าย: ข้อมูลทั่วไป --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-semibold text-secondary border-bottom pb-3 mb-3">ข้อมูลทั่วไป</h5>

                        <dl class="row mb-0">
                            <dt class="col-5 fw-normal text-muted mb-3">เลขที่</dt>
                            <dd class="col-7 mb-3">{{ $quotation->code_quot }}</dd>

                            <dt class="col-5 fw-normal text-muted mb-3">ลูกค้า</dt>
                            <dd class="col-7 mb-3">{{ $quotation->customer->name_customer ?? '-' }}</dd>

                            <dt class="col-5 fw-normal text-muted mb-3">วันที่ออก</dt>
                            <dd class="col-7 mb-3">
                                {{ \Carbon\Carbon::parse($quotation->date_quot)->format('d/m/Y') }}
                            </dd>

                            <dt class="col-5 fw-normal text-muted mb-3">สถานะ</dt>
                            <dd class="col-7 mb-3">
                                <span class="badge bg-{{ $status['badge'] }}">{{ $status['label'] }}</span>
                            </dd>

                            <dt class="col-5 fw-normal text-muted mb-3">ใบสั่งขาย</dt>
                            <dd class="col-7 mb-3">
                                @if ($so)
                                    <a href="{{ route('sales-orders.show', $so->id_so) }}"
                                        class="text-decoration-none">{{ $so->code_so }}</a>
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-5 fw-normal text-muted">แคมป์งาน</dt>
                            <dd class="col-7 mb-0">
                                @if ($so?->camp)
                                    <a href="{{ route('camps.show', $so->camp->id_camp) }}"
                                        class="text-decoration-none">{{ $so->camp->code_camp }}</a>
                                    <div class="small text-muted">{{ $so->camp->name_camp }}</div>
                                @else
                                    <span class="text-muted">ยังไม่ได้เปิด</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- ขวา: รายการสินค้า --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-semibold text-secondary border-bottom pb-3 mb-3">รายการสินค้าที่เสนอราคา</h5>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:8%">ลำดับ</th>
                                        <th>สินค้า</th>
                                        <th class="text-end" style="width:15%">จำนวน</th>
                                        <th class="text-end" style="width:18%">ราคา/หน่วย</th>
                                        <th class="text-end" style="width:18%">รวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($quotation->details as $i => $d)
                                        <tr>
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td class="fw-semibold">{{ $d->product->name_product ?? '-' }}</td>
                                            <td class="text-end">{{ number_format($d->quantity, 2) }}</td>
                                            <td class="text-end">{{ number_format($d->price_per_unit, 2) }}</td>
                                            <td class="text-end">{{ number_format($d->total_price, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">ไม่มีรายการสินค้า</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end text-muted">รวมเป็นเงิน</td>
                                        <td class="text-end fw-bold">{{ number_format($subTotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end text-muted">ส่วนลด</td>
                                        <td class="text-end fw-bold">{{ number_format($discount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end text-muted">ภาษีมูลค่าเพิ่ม 7%</td>
                                        <td class="text-end fw-bold">{{ number_format($vat, 2) }}</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td colspan="4" class="text-end fw-bold">ยอดสุทธิ</td>
                                        <td class="text-end fw-bold">{{ number_format($grandTotal, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ==================== ย้อนกลับ ==================== --}}
        <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">
            ย้อนกลับ
        </a>

    </div>
@endsection