@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบแจ้งหนี้</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">


        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>เลขที่</th>
                        <th>ลูกค้า</th>
                        <th>อ้างอิง</th>
                        <th class="text-end">ยอดหลังหักส่วนลด (ไม่รวม VAT)</th>
                        <th class="text-end">ยอดสุทธิ (รวม VAT)</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($invoices as $inv)
                        @php
                            $subTotal = $inv->quotation->subtotal ?? 0;
                            $discount = $inv->quotation->discount ?? 0;

                            $afterDiscount = max($subTotal - $discount, 0);

                            $vat = $afterDiscount * 0.07;

                            $grandTotal = $afterDiscount + $vat;
                        @endphp

                        <tr>

                            <td>
                                <strong>
                                    INV{{ str_pad($inv->id_invoice, 5, '0', STR_PAD_LEFT) }}
                                </strong>
                            </td>

                            <td>
                                {{ $inv->customer->name_customer ?? '-' }}
                            </td>

                            <td>
                                QT{{ str_pad($inv->id_quotation, 5, '0', STR_PAD_LEFT) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($afterDiscount, 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($grandTotal, 2) }}
                            </td>

                            <td class="text-center">
                                @if ($inv->status == 'paid')
                                    <span class="badge bg-success">ชำระแล้ว</span>
                                @else
                                    <span class="badge bg-warning text-dark">ยังไม่ชำระ</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="btn-group">

                                    <a href="{{ route('invoices.show', $inv->id_invoice) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        ดู
                                    </a>

                                    <form method="POST" action="{{ route('invoices.destroy', $inv) }}" class="d-inline"
                                        data-confirm="ข้อมูล {{ $inv->id_invoice }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบข้อมูล" data-confirm-variant="danger"
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
                            <td colspan="7" class="text-center text-muted py-4">
                                — ยังไม่มีใบแจ้งหนี้ —
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <div class="mt-3">
            {{ $invoices->links() }}
        </div>

    </div>
@endsection
