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
                        <th class="text-center" style="width:140px;">จัดการ</th>
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

                            $receiptCode = 'RC' . str_pad($r->id_receipt, 5, '0', STR_PAD_LEFT);
                        @endphp

                        <tr>
                            <td>
                                <strong>{{ $receiptCode }}</strong>
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
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('receipts.show', $r->id_receipt) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูใบเสร็จ {{ $receiptCode }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล --}}
                                    <form method="POST"
                                        action="{{ route('receipts.destroy', $r) }}"
                                        class="delete-form"
                                        data-confirm="ใบเสร็จ {{ $receiptCode }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบข้อมูล"
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-outline-danger action-button"
                                            type="submit"
                                            title="ลบข้อมูล"
                                            aria-label="ลบใบเสร็จ {{ $receiptCode }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ยังไม่มีรายการใบเสร็จ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $receipts->withQueryString()->links() }}
        </div>

    </div>
@endsection