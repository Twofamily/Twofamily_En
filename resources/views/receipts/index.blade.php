@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบเสร็จ</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <div class="table-responsive shadow-sm">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>ลูกค้า</th>
                        <th>อ้างอิง</th>
                        <th class="text-end">ยอดสุทธิ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($receipts as $r)
                        @php
                            $inv = $r->invoice;
                            $subTotal = $inv->quotation->subtotal ?? 0;
                            $discount = $inv->quotation->discount ?? 0;

                            $afterDiscount = max($subTotal - $discount, 0);
                            $vat = $afterDiscount * 0.07;

                            $grandTotal = $afterDiscount + $vat;
                        @endphp

                        <tr>
                            <td>RC{{ str_pad($r->id_receipt, 5, '0', STR_PAD_LEFT) }}</td>

                            <td>{{ $inv->customer->name_customer }}</td>

                            <td>INV{{ str_pad($inv->id_invoice, 5, '0', STR_PAD_LEFT) }}</td>

                            <td class="text-end">
                                {{ number_format($grandTotal, 2) }}
                            </td>

                            <td class="text-center">
                                <a href="{{ route('receipts.show', $r->id_receipt) }}" class="btn btn-sm btn-primary">
                                    ดู
                                </a>

                                <form method="POST" action="{{ route('receipts.destroy', $item) }}" class="d-inline"
                                    data-confirm="ข้อมูล [ชื่อรายการ] จะถูกลบถาวร ไม่สามารถกู้คืนได้"
                                    data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger"
                                    data-confirm-ok="ลบข้อมูล">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $receipts->links() }}

    </div>
@endsection
