{{--
    หน้าต่างแก้ไขใบสั่งขาย (หน้าดูใบสั่งขาย) — แก้ได้เฉพาะใบที่ยังเป็นร่าง
    ต้องส่ง: $salesOrder, $products
    กรอกผิดแล้วเด้งกลับ (error bag updateSalesOrder) หรือมาจากลิงก์ ?edit=1 → หน้าต่างเปิดเอง
--}}
@php
    $bag    = $errors->updateSalesOrder;
    $hasOld = $bag->any();

    $rows = $hasOld
        ? array_values(old('items', []))
        : $salesOrder->details->map(fn ($d) => [
            'id_product' => $d->id_product,
            'quantity'   => (float) $d->quantity,
            'price'      => (float) $d->price_per_unit,
        ])->values()->all();

    $val = fn (string $key, $current) => $hasOld ? old($key) : $current;
@endphp

<div class="modal fade" id="editSalesOrderModal" tabindex="-1" aria-labelledby="editSalesOrderTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('sales_orders.update', $salesOrder) }}" autocomplete="off"
              data-confirm="ต้องการบันทึกการแก้ไขใบสั่งขายนี้หรือไม่" data-confirm-title="ยืนยันการแก้ไขข้อมูล"
              data-confirm-ok="บันทึกการแก้ไข" data-confirm-variant="primary">
            @csrf
            @method('PUT')
            <input type="hidden" name="id_quot" value="{{ $salesOrder->id_quot }}">
            <input type="hidden" name="id_customer" value="{{ $salesOrder->id_customer }}">

            <div class="modal-header">
                <h5 class="modal-title" id="editSalesOrderTitle">
                    <i class="bi bi-pencil-square me-1"></i>แก้ไขใบสั่งขาย
                    <span class="text-muted fw-normal fs-6">— {{ $salesOrder->code_so }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body">
                @include('partials.modal-errors', ['bag' => $bag])

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">ลูกค้า</label>
                        <input type="text" class="form-control bg-light" value="{{ $salesOrder->customer->name_customer ?? '-' }}" readonly>
                        <div class="form-text">เปลี่ยนลูกค้าไม่ได้ ถ้าผิดให้ยกเลิกใบนี้แล้วสร้างใหม่</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">อ้างอิงใบเสนอราคา</label>
                        <input type="text" class="form-control bg-light" value="{{ $salesOrder->quotation->code_quot ?? 'สั่งตรง' }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="e_order_date" class="form-label">วันที่สั่งซื้อ</label>
                        <input type="date" id="e_order_date" name="order_date" class="form-control" required
                               value="{{ $val('order_date', $salesOrder->order_date?->toDateString()) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="e_due_date" class="form-label">กำหนดส่งมอบ <span class="text-muted fw-normal">(ไม่บังคับ)</span></label>
                        <input type="date" id="e_due_date" name="due_date" class="form-control"
                               value="{{ $val('due_date', $salesOrder->due_date?->toDateString()) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="e_po_number" class="form-label">เลขที่ PO ของลูกค้า <span class="text-muted fw-normal">(ไม่บังคับ)</span></label>
                        <input type="text" id="e_po_number" name="po_number" class="form-control" maxlength="50"
                               value="{{ $val('po_number', $salesOrder->po_number) }}">
                    </div>
                    <div class="col-12">
                        <label for="e_note" class="form-label">หมายเหตุ</label>
                        <textarea id="e_note" name="note" rows="2" class="form-control">{{ $val('note', $salesOrder->note) }}</textarea>
                    </div>
                </div>

                @include('sales_orders._items', ['products' => $products, 'rows' => $rows])

                @if ($salesOrder->isDirect())
                    <div class="form-text">ใบสั่งตรง — แก้ราคาแล้วต้องให้ผู้ดูแลระบบตรวจและยืนยันอีกครั้ง</div>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

@if ($hasOld || request()->boolean('edit'))
    <script>
        document.addEventListener('DOMContentLoaded', () =>
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editSalesOrderModal')).show());
    </script>
@endif
