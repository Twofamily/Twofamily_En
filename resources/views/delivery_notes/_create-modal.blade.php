{{--
    หน้าต่างสร้างใบส่งของ (หน้ารายการใบส่งของ)
      ขั้นที่ 1 เลือกแคมป์ → โหลดหน้าใหม่ ?modal=create&camp=ID ให้ระบบดึงรายการตามสัญญามาเติม
      ขั้นที่ 2 กรอกจำนวนที่ส่งเที่ยวนี้

    ต้องส่ง: $camps, $camp (Camp|null), $suggestedItems
    กรอกผิดแล้วเด้งกลับ (error bag createDeliveryNote) → หน้าต่างเปิดเองพร้อมค่าที่กรอกไว้
--}}
@php
    // ข้อผิดพลาดของหน้าต่างนี้แยกกล่อง — ใช้ชื่อ $errors เดิมภายในไฟล์นี้ (@error ทำงานตามปกติ)
    $errors = (new \Illuminate\Support\ViewErrorBag())->put('default', $errors->createDeliveryNote);
@endphp

<div class="modal fade" id="createDeliveryNoteModal" tabindex="-1" aria-labelledby="createDeliveryNoteTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createDeliveryNoteTitle"><i class="bi bi-truck me-1"></i>สร้างใบส่งของ</h5>
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

                {{-- ขั้นที่ 1 เลือกแคมป์ --}}
                <form method="GET" action="{{ route('delivery_notes.index') }}" class="mb-4">
                    <input type="hidden" name="modal" value="create">
                    <label for="camp_picker" class="form-label">เลือกแคมป์</label>

                    <div class="d-flex gap-2">
                        <select id="camp_picker" name="camp" class="form-select" onchange="this.form.submit()">
                            <option value="">— เลือกแคมป์ —</option>
                            @foreach ($camps as $c)
                                <option value="{{ $c->id_camp }}" @selected($camp?->id_camp == $c->id_camp)>
                                    {{ $c->code_camp }} — {{ $c->name_camp }}
                                    ({{ $c->customer->name_customer ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-text">แสดงเฉพาะแคมป์ที่อยู่ในสถานะเตรียมงาน หรือกำลังดำเนินงาน</div>
                </form>

                @if ($camp)
                    {{-- ข้อมูลที่ดึงมาอัตโนมัติ --}}
                    <div class="border rounded-3 p-3 mb-4 bg-light">
                        <div class="row g-2 small">
                            <div class="col-md-4"><strong>ลูกค้า:</strong> {{ $camp->customer->name_customer ?? '-' }}</div>
                            <div class="col-md-4">
                                <strong>อ้างอิงสัญญา:</strong>
                                {{ $camp->contract_code ?? 'ไม่ได้อ้างอิง' }}
                            </div>
                            <div class="col-md-4"><strong>สถานที่ส่ง:</strong> {{ $camp->full_address ?: '-' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('delivery_notes.store') }}"
                        data-confirm="ต้องการบันทึกใบส่งของนี้หรือไม่ ตรวจสอบจำนวนและรายการให้ถูกต้องก่อนยืนยัน" data-confirm-title="ยืนยันการบันทึกข้อมูล" data-confirm-ok="บันทึก" data-confirm-variant="primary">
                        @csrf
                        <input type="hidden" name="id_camp" value="{{ $camp->id_camp }}">

                        <div class="mb-3 col-md-4">
                            <label for="delivery_date" class="form-label">วันที่ส่งของ</label>
                            <input type="date" id="delivery_date" name="delivery_date"
                                   value="{{ old('delivery_date', now()->format('Y-m-d')) }}"
                                   class="form-control @error('delivery_date') is-invalid @enderror" required>
                            @error('delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if ($suggestedItems->isEmpty())
                            <div class="alert alert-warning">
                                แคมป์นี้ยังไม่มีใบสั่งขาย (หรือใบเสนอราคาเดิม) จึงไม่มีรายการให้เติมอัตโนมัติ
                                กรุณาเพิ่มใบสั่งขายเข้าแคมป์ก่อน จากหน้าใบสั่งขาย
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>สินค้า</th>
                                            <th class="text-end">ตามสัญญา</th>
                                            <th class="text-end">ส่งแล้ว</th>
                                            <th class="text-end">คงเหลือ</th>
                                            <th style="width:140px">จำนวนที่ส่ง</th>
                                            <th style="width:140px">ราคา/หน่วย</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($suggestedItems as $i => $item)
                                            <tr @class(['table-secondary' => $item['remaining'] <= 0])>
                                                <td>
                                                    {{ $item['name_product'] }}
                                                    <input type="hidden" name="items[{{ $i }}][id_product]"
                                                           value="{{ $item['id_product'] }}">
                                                </td>
                                                <td class="text-end">{{ number_format($item['ordered'], 2) }}</td>
                                                <td class="text-end">{{ number_format($item['delivered'], 2) }}</td>
                                                <td class="text-end fw-semibold">
                                                    {{ number_format($item['remaining'], 2) }}
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0"
                                                           name="items[{{ $i }}][quantity]"
                                                           value="{{ old("items.$i.quantity", $item['remaining']) }}"
                                                           class="form-control form-control-sm text-end @error("items.$i.quantity") is-invalid @enderror">
                                                    @error("items.$i.quantity")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0"
                                                           name="items[{{ $i }}][price]"
                                                           value="{{ old("items.$i.price", $item['price_per_unit']) }}"
                                                           class="form-control form-control-sm text-end @error("items.$i.price") is-invalid @enderror">
                                                    @error("items.$i.price")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-text mb-2">
                                ใส่จำนวน 0 ในรายการที่ไม่ได้ส่งเที่ยวนี้ ระบบจะไม่นำรายการนั้นมาออกใบส่งของ
                            </div>

                            @error('items')
                                <div class="text-danger small mb-2">{{ $message }}</div>
                            @enderror

                            {{-- ส่งเกินจำนวนตามสัญญา: ต้องติ๊กยืนยันก่อนถึงบันทึกได้ --}}
                            @if (session('over_contract') || old('confirm_over'))
                                <div class="alert alert-warning py-2">
                                    <div class="fw-semibold mb-1">
                                        <i class="bi bi-exclamation-triangle me-1"></i>จำนวนที่ส่งเกินคงเหลือตามสัญญา
                                    </div>
                                    <div class="small mb-2">
                                        ตรวจรายการที่มีกรอบสีแดง ถ้าตั้งใจส่งเกินจริง (เช่น ลูกค้าขอเพิ่มหน้างาน)
                                        ควรเปิดใบสั่งขายเพิ่มให้ครอบคลุมด้วย
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="confirm_over" value="1"
                                               id="confirm_over" @checked(old('confirm_over'))>
                                        <label class="form-check-label" for="confirm_over">ยืนยันส่งเกินจำนวนตามสัญญา</label>
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึกใบส่งของ</button>
                            </div>
                        @endif
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
