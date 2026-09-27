@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบส่งของ {{ $deliveryNote->code_delivery }}</h3>
    </div>
@endsection

@section('content')
<div class="container py-3">

    {{-- ส่วนหัวเอกสาร (แบบเดียวกับใบเสนอราคา) --}}
    <div class="text-center mb-4">
        <h2>ใบส่งของ (Delivery Note)</h2>
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
                @if ($deliveryNote->invoice)
                    <span class="text-success">
                        <i class="bi bi-check-circle-fill me-1"></i> ออกใบแจ้งหนี้แล้ว
                    </span>
                    <a href="{{ route('invoices.show', $deliveryNote->invoice) }}"
                       class="btn btn-sm btn-outline-dark">
                        {{ $deliveryNote->invoice->code_inv }}
                    </a>
                @else
                    <span class="text-primary">
                        <i class="bi bi-arrow-right-circle me-1"></i> พร้อมออกใบแจ้งหนี้
                    </span>
                    <form method="POST" action="{{ route('invoices.createFromDeliveryNote', $deliveryNote) }}"
                          style="display:inline;"
                          data-confirm="ระบบจะสร้างใบแจ้งหนี้จากใบส่งของ {{ $deliveryNote->code_delivery }}"
                          data-confirm-title="ยืนยันการออกใบแจ้งหนี้"
                          data-confirm-variant="success"
                          data-confirm-ok="ออกใบแจ้งหนี้">
                        @csrf
                        <button class="btn btn-sm btn-primary" type="submit">
                            ออกใบแจ้งหนี้จากใบส่งของนี้
                        </button>
                    </form>
                @endif
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('delivery-notes.pdf', $deliveryNote) }}"
                   class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-printer me-1"></i> พิมพ์
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">ข้อมูลทั่วไป</h6>

                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal">สถานะ</dt>
                        <dd class="col-7">
                            <span class="badge bg-success">ส่งของแล้ว</span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ลูกค้า</dt>
                        <dd class="col-7">
                            {{ $deliveryNote->customer->name_customer ?? '-' }}
                            <div class="text-muted">{{ customer_address($deliveryNote->customer) }}</div>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">วันที่ส่ง</dt>
                        <dd class="col-7">{{ $deliveryNote->delivery_date?->format('d/m/Y') ?? '-' }}</dd>

                        <dt class="col-5 text-muted fw-normal">แคมป์ / สถานที่ส่ง</dt>
                        <dd class="col-7">
                            @if ($deliveryNote->camp)
                                <a href="{{ route('camps.show', $deliveryNote->camp->id_camp) }}"
                                   class="text-decoration-none">
                                    {{ $deliveryNote->camp->code_camp }}
                                </a>
                                <div>{{ $deliveryNote->camp->name_camp }}</div>
                                <div class="text-muted">{{ $deliveryNote->camp->full_address ?: '-' }}</div>
                            @else
                                <span class="text-muted">ไม่ได้ระบุแคมป์</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ใบเสนอราคา</dt>
                        <dd class="col-7">
                            @if ($deliveryNote->quotation)
                                <a href="{{ route('quotations.show', $deliveryNote->quotation) }}"
                                   class="text-decoration-none">
                                    {{ $deliveryNote->quotation->code_quot }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">ใบแจ้งหนี้</dt>
                        <dd class="col-7">
                            @if ($deliveryNote->invoice)
                                <a href="{{ route('invoices.show', $deliveryNote->invoice) }}"
                                   class="text-decoration-none">
                                    {{ $deliveryNote->invoice->code_inv }}
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
                    <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">รายการสินค้าที่ส่ง</h6>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%" class="text-center">ลำดับ</th>
                                    <th>สินค้า</th>
                                    <th class="text-center" style="width:13%">จำนวน</th>
                                    <th class="text-center" style="width:10%">หน่วย</th>
                                    <th class="text-end" style="width:18%">ราคา/หน่วย</th>
                                    <th class="text-end" style="width:18%">รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($deliveryNote->details as $i => $detail)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>{{ $detail->product->name_product ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($detail->quantity, 2) }}</td>
                                        <td class="text-center">คิว</td>
                                        <td class="text-end">{{ number_format($detail->price_per_unit, 2) }}</td>
                                        <td class="text-end">{{ number_format($detail->total_price, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            ยังไม่มีรายการสินค้า
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="table-primary fw-bold">
                                    <th colspan="5" class="text-end">รวมทั้งสิ้น</th>
                                    <th class="text-end">
                                        {{ number_format($deliveryNote->details->sum('total_price'), 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('delivery-notes.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
    </div>
</div>
@endsection