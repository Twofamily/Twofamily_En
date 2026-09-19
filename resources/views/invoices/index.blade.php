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
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($invoices as $inv)
                        @php
                            // เลขที่อ่านจากคอลัมน์ code_inv ที่เดียว (INV-2569-0001)
                            $code = $inv->code_inv;

                            /*
                             * ยอดเงิน: ใช้วิธีเดียวกับ PDF ใบแจ้งหนี้ ตัวเลขในหน้านี้กับใน PDF จะได้ตรงกัน
                             * ใช้คอลัมน์ที่บันทึกไว้ในตาราง invoices ก่อน
                             * ถ้ายังไม่มีคอลัมน์ จะถอยไปใช้วิธีเดิม (อ่านจากใบเสนอราคาแล้วคำนวณ)
                             */
                            $q = $inv->quotation;

                            $subTotal      = $inv->subtotal ?? $q?->subtotal ?? 0;
                            $discount      = $inv->discount ?? $q?->discount ?? 0;
                            $afterDiscount = $inv->after_discount ?? max($subTotal - $discount, 0);
                            $vatRate       = $inv->vat_rate ?? 7;
                            $vat           = $inv->vat_amount ?? $afterDiscount * $vatRate / 100;
                            $grandTotal    = $inv->total_amount ?? $afterDiscount + $vat;

                            $isPaid = $inv->status == 'paid';
                        @endphp

                        <tr>
                            <td>
                                <strong>{{ $code }}</strong>
                            </td>

                            <td>
                                {{ $inv->customer->name_customer ?? '-' }}
                            </td>

                            <td>
                                {{ $q?->code_quot ?? '-' }}
                            </td>

                            <td class="text-end">
                                {{ number_format($afterDiscount, 2) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($grandTotal, 2) }}
                            </td>

                            <td class="text-center">
                                @if ($isPaid)
                                    <span class="badge bg-success">ชำระแล้ว</span>
                                @else
                                    <span class="badge bg-warning text-dark">ยังไม่ชำระ</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('invoices.show', $inv->id_invoice) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูใบแจ้งหนี้ {{ $code }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล: ซ่อนเมื่อชำระแล้ว เอกสารที่ปิดงานแล้วไม่ควรถูกลบ --}}
                                    @unless ($isPaid)
                                        <form method="POST"
                                            action="{{ route('invoices.destroy', $inv) }}"
                                            class="delete-form"
                                            data-confirm="ใบแจ้งหนี้ {{ $code }} จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบข้อมูล"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-outline-danger action-button"
                                                type="submit"
                                                title="ลบข้อมูล"
                                                aria-label="ลบใบแจ้งหนี้ {{ $code }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ยังไม่มีใบแจ้งหนี้
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $invoices->withQueryString()->links() }}
        </div>

    </div>
@endsection