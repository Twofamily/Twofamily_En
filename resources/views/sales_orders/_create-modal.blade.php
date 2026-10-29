{{--
    หน้าต่างสร้างใบสั่งขาย (หน้ารายการใบสั่งขาย) — 2 ทาง
      ?modal=create&quotation=ID : จากใบเสนอราคาที่อนุมัติแล้ว (ราคาตามใบเสนอราคา)
      ?modal=create&direct=1     : สั่งตรง ไม่มีใบเสนอราคา — ต้องให้ผู้ดูแลระบบยืนยันราคา
      ?modal=create              : เลือกว่าจะสร้างแบบไหน

    ต้องส่ง: $quotation (Quotation|null), $direct (bool), $quotations, $customers, $products
    กรอกผิดแล้วเด้งกลับ (error bag createSalesOrder) → หน้าต่างเปิดเองพร้อมค่าที่กรอกไว้
    ใบสั่งตรง: ปุ่ม "ลูกค้าใหม่" → หน้าต่างเพิ่มลูกค้า (customers._quick-modal) ไม่ต้องเปิดแท็บใหม่
--}}
@php
    $errors = $errors->createSalesOrder->any()
        ? (new \Illuminate\Support\ViewErrorBag())->put('default', $errors->createSalesOrder)
        : new \Illuminate\Support\ViewErrorBag();
@endphp

<div class="modal fade" id="createSalesOrderModal" tabindex="-1" aria-labelledby="createSalesOrderTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSalesOrderTitle"><i class="bi bi-cart-plus me-1"></i>สร้างใบสั่งขาย</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                @if ($errors->any())
                    <div class="alert alert-danger py-2" data-modal-errors>
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- ==================== ขั้นที่ 1: เลือกว่าจะสร้างแบบไหน ==================== --}}
                @if (! $quotation && ! $direct)
                    <div class="row g-3">
                        {{-- จากใบเสนอราคา --}}
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-body p-4">
                                    <h6 class="fw-semibold border-bottom pb-2 mb-3">
                                        <i class="bi bi-file-earmark-text me-1"></i>สร้างจากใบเสนอราคา
                                    </h6>
                                    <p class="small text-muted">
                                        ลูกค้าตกลงตามใบเสนอราคาที่อนุมัติแล้ว — ราคาดึงมาจากใบเสนอราคา ยืนยันใบสั่งขายได้เลย
                                    </p>

                                    @if ($quotations->isEmpty())
                                        <p class="text-muted mb-0">
                                            ยังไม่มีใบเสนอราคาที่พร้อมออกใบสั่งขาย
                                            (ต้องอนุมัติแล้ว และยังไม่เคยออกใบสั่งขาย)
                                        </p>
                                    @else
                                        <div class="list-group">
                                            @foreach ($quotations as $item)
                                                <a href="{{ route('sales_orders.index', ['modal' => 'create', 'quotation' => $item->id_quot]) }}"
                                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="fw-semibold">{{ $item->code_quot }}</span>
                                                        <span class="text-muted ms-2">{{ $item->customer->name_customer ?? '-' }}</span>
                                                        <div class="small text-muted">
                                                            ลงวันที่ {{ $item->date_quot?->format('d/m/Y') }}
                                                        </div>
                                                    </div>
                                                    <span class="fw-semibold">{{ number_format($item->total_amount, 2) }} บาท</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- สั่งตรง --}}
                        <div class="col-lg-5">
                            <div class="card h-100">
                                <div class="card-body p-4 d-flex flex-column">
                                    <h6 class="fw-semibold border-bottom pb-2 mb-3">
                                        <i class="bi bi-telephone-inbound me-1"></i>สั่งตรง (ไม่มีใบเสนอราคา)
                                    </h6>
                                    <p class="small text-muted">
                                        เช่น ลูกค้าประจำโทรสั่งเพิ่ม ตกลงราคากันแล้วไม่ต้องออกใบเสนอราคาใหม่
                                    </p>
                                    <ul class="small text-muted ps-3">
                                        <li>เลือกลูกค้าและกรอกรายการเอง (ราคาเริ่มต้นดึงจากหน้าสินค้า แก้ได้)</li>
                                        <li>บันทึกเป็นร่างก่อน แล้ว<strong>ผู้ดูแลระบบ</strong>ต้องตรวจราคาและกดยืนยัน จึงจะเปิดแคมป์ได้</li>
                                    </ul>
                                    <div class="mt-auto">
                                        <a href="{{ route('sales_orders.index', ['modal' => 'create', 'direct' => 1]) }}" class="btn btn-outline-dark w-100">
                                            <i class="bi bi-plus-lg me-1"></i>สร้างใบสั่งตรง
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ==================== ขั้นที่ 2: กรอกใบสั่งขาย ==================== --}}
                @if ($quotation || $direct)
                    @php
                        $rows = old('items', $quotation
                            ? $quotation->details->map(fn ($d) => [
                                'id_product' => $d->id_product,
                                'quantity'   => (float) $d->quantity,
                                'price'      => (float) $d->price_per_unit,
                            ])->values()->all()
                            : []);
                    @endphp

                    @if ($quotation)
                        <div class="alert alert-info d-flex justify-content-between align-items-center">
                            <div>
                                สร้างจากใบเสนอราคา <strong>{{ $quotation->code_quot }}</strong>
                                — {{ $quotation->customer->name_customer ?? '-' }}
                            </div>
                            <a href="{{ route('sales_orders.index', ['modal' => 'create']) }}" class="btn btn-sm btn-outline-secondary">
                                เปลี่ยนใบเสนอราคา
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <i class="bi bi-telephone-inbound me-1"></i>
                                <strong>ใบสั่งตรง</strong> — ไม่มีใบเสนอราคา ราคาที่กรอกต้องให้ผู้ดูแลระบบตรวจและยืนยันก่อนเปิดแคมป์
                            </div>
                            <a href="{{ route('sales_orders.index', ['modal' => 'create']) }}" class="btn btn-sm btn-outline-secondary text-nowrap">
                                เลือกจากใบเสนอราคาแทน
                            </a>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('sales_orders.store') }}" autocomplete="off"
                        data-confirm="{{ $quotation ? 'ต้องการบันทึกใบสั่งขายนี้หรือไม่' : 'ใบสั่งตรงจะถูกบันทึกเป็นร่าง และรอผู้ดูแลระบบตรวจราคาและยืนยัน' }}"
                        data-confirm-title="ยืนยันการบันทึกข้อมูล" data-confirm-ok="บันทึก" data-confirm-variant="primary">
                        @csrf

                        @if ($quotation)
                            <input type="hidden" name="id_quot" value="{{ $quotation->id_quot }}">
                            <input type="hidden" name="id_customer" value="{{ $quotation->id_customer }}">
                        @else
                            <input type="hidden" name="direct" value="1">
                        @endif

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-4">
                                <h6 class="text-muted fw-semibold border-bottom pb-2 mb-3">ข้อมูลใบสั่งขาย</h6>

                                <div class="row g-3">
                                    @unless ($quotation)
                                        <div class="col-12">
                                            <label for="id_customer" class="form-label">ลูกค้า</label>
                                            <div class="d-flex gap-2">
                                                <select id="id_customer" name="id_customer"
                                                        class="form-select @error('id_customer') is-invalid @enderror"
                                                        required data-required-msg="กรุณาเลือกลูกค้า" data-customer-select>
                                                    <option value="">— เลือกลูกค้า —</option>
                                                    @foreach ($customers as $c)
                                                        @continue($c->isWalkIn())
                                                        <option value="{{ $c->id_customer }}" @selected(old('id_customer') == $c->id_customer)>
                                                            {{ $c->name_customer }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-outline-secondary text-nowrap" data-quick-customer>
                                                    <i class="bi bi-person-plus me-1"></i>ลูกค้าใหม่
                                                </button>
                                            </div>
                                        </div>
                                    @endunless

                                    <div class="col-md-4">
                                        <label for="order_date" class="form-label">วันที่สั่งซื้อ</label>
                                        <input type="date" id="order_date" name="order_date"
                                               value="{{ old('order_date', now()->toDateString()) }}"
                                               class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="due_date" class="form-label">
                                            กำหนดส่งมอบ <span class="text-muted fw-normal">(ไม่บังคับ)</span>
                                        </label>
                                        <input type="date" id="due_date" name="due_date"
                                               value="{{ old('due_date') }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="po_number" class="form-label">
                                            เลขที่ PO ของลูกค้า <span class="text-muted fw-normal">(ไม่บังคับ)</span>
                                        </label>
                                        <input type="text" id="po_number" name="po_number"
                                               value="{{ old('po_number') }}" class="form-control" maxlength="50">
                                    </div>
                                    <div class="col-12">
                                        <label for="note" class="form-label">หมายเหตุ</label>
                                        <textarea id="note" name="note" rows="2" class="form-control"
                                                  placeholder="{{ $quotation ? '' : 'เช่น ลูกค้าโทรสั่งเพิ่ม ตกลงราคาตามงานเดิม' }}">{{ old('note') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @include('sales_orders._items', [
                            'products'      => $products,
                            'rows'          => $rows,
                        ])

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึกเป็นร่าง</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ปุ่ม "ลูกค้าใหม่" (ใบสั่งตรง) เปิดหน้าต่างนี้แทน แล้วเลือกลูกค้าที่เพิ่งเพิ่มให้เลย --}}
@if ($direct && ! $quotation)
    @include('customers._quick-modal', ['action' => route('sales_orders.customers.store')])
@endif
