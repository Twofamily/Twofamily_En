@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบส่งของ {{ $deliveryNote->code_delivery }}</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">


        <div id="delivery-note">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">{{ $deliveryNote->code_delivery }}</h5>
                <span class="badge bg-success fs-6">ส่งของแล้ว</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="text-muted small">ลูกค้า</div>
                    <div>{{ $deliveryNote->customer->name_customer ?? '-' }}</div>
                    <div class="small text-muted">{{ customer_address($deliveryNote->customer) }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">แคมป์ / สถานที่ส่ง</div>
                    @if ($deliveryNote->camp)
                        <div>
                            <a href="{{ route('camps.show', $deliveryNote->camp->id_camp) }}"
                               class="text-decoration-none">
                                {{ $deliveryNote->camp->code_camp }} — {{ $deliveryNote->camp->name_camp }}
                            </a>
                        </div>
                        <div class="small text-muted">{{ $deliveryNote->camp->full_address ?: '-' }}</div>
                    @else
                        <div class="text-muted">ไม่ได้ระบุแคมป์</div>
                    @endif
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">วันที่ส่ง</div>
                    <div>{{ $deliveryNote->delivery_date->format('d/m/Y') }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">อ้างอิงใบเสนอราคา</div>
                    <div>
                        @if ($deliveryNote->quotation)
                            <a href="{{ route('quotations.show', $deliveryNote->quotation) }}"
                               class="text-decoration-none">
                                {{ $deliveryNote->quotation->code_quot }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 8%">ลำดับ</th>
                            <th>รายการ</th>
                            <th class="text-end" style="width: 15%">จำนวน</th>
                            <th class="text-center" style="width: 12%">หน่วย</th>
                            <th class="text-end" style="width: 15%">ราคา/หน่วย</th>
                            <th class="text-end" style="width: 15%">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deliveryNote->details as $index => $detail)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $detail->product->name_product ?? '-' }}</td>
                                <td class="text-end">{{ number_format($detail->quantity, 2) }}</td>
                                <td class="text-center">คิว</td>
                                <td class="text-end">{{ number_format($detail->price_per_unit, 2) }}</td>
                                <td class="text-end">{{ number_format($detail->total_price, 2) }}</td>
                            </tr>
                        @endforeach
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

        <!-- ปรับแต่งส่วนปุ่มให้ตรงกับใบแจ้งหนี้ -->
        <div class="text-center mb-5">

            <a href="{{ route('delivery-notes.index') }}" class="btn btn-outline-secondary">
                ย้อนกลับ
            </a>

            <a href="{{ route('delivery-notes.pdf', $deliveryNote) }}" target="_blank" class="btn btn-danger">
                ดาวน์โหลด PDF
            </a>

            @if ($deliveryNote->invoice)
                <a href="{{ route('invoices.show', $deliveryNote->invoice) }}" class="btn btn-success">
                    ดูใบแจ้งหนี้
                </a>
            @else
                <form action="{{ route('invoices.createFromDeliveryNote', $deliveryNote) }}"
                      method="POST" style="display:inline;">
                    @csrf
                    <button class="btn btn-primary">
                        ออกใบแจ้งหนี้
                    </button>
                </form>
            @endif

        </div>
    </div>
@endsection