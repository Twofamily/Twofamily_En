@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบเสร็จ</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>เลขที่ใบเสร็จ</th>
                        <th>ลูกค้า</th>
                        <th>อ้างอิงใบแจ้งหนี้</th>
                        <th class="text-end">ยอดสุทธิ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($receipts as $r)
                        @php
                            $inv = $r->invoice;
                            $subTotal = $inv->quotation->subtotal ?? 0;
                            $discount = $inv->quotation->discount ?? 0;

                            $afterDiscount = max($subTotal - $discount, 0);
                            $vat = $afterDiscount * 0.07;

                            $grandTotal = $afterDiscount + $vat;
                        @endphp

                        <tr>
                            <td>
                                <strong>
                                    RC{{ str_pad($r->id_receipt, 5, '0', STR_PAD_LEFT) }}
                                </strong>
                            </td>

                            <td>
                                {{ $inv->customer->name_customer ?? '-' }}
                            </td>

                            <td>
                                INV{{ str_pad($inv->id_invoice ?? 0, 5, '0', STR_PAD_LEFT) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($grandTotal, 2) }}
                            </td>

                            <td class="text-center">
                                <div class="btn-group">
                                    <!-- ดู -->
                                    <a href="{{ route('receipts.show', $r->id_receipt) }}" class="btn btn-sm btn-outline-primary">
                                        ดู
                                    </a>

                                    <!-- ลบ -->
                                    <form method="POST" action="{{ route('receipts.destroy', $r) }}" class="d-inline"
                                        data-confirm="ข้อมูล RC{{ str_pad($r->id_receipt, 5, '0', STR_PAD_LEFT) }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบข้อมูล" 
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf 
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                — ยังไม่มีรายการใบเสร็จ —
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $receipts->links() }}
        </div>

    </div>
@endsection