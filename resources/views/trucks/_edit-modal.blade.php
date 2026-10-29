{{--
    หน้าต่างแก้ไขรถบรรทุก (ใช้ร่วมทุกแถวในแท็บรถบรรทุก)
    ต้องส่ง: $brandOptions (ยี่ห้อ + รุ่น), $truckEditData (closure), $openEditTruck (Truck|null), $oldEdit (array|null)
    ปุ่มในแถว: <button data-truck-edit='@json($truckEditData($t))'>
--}}
@php
    $te   = $errors->updateTruck;
    $bad  = fn (string $key) => $te->has($key) ? 'is-invalid' : '';
    $ymax = now()->year;
    $keepQuery = array_filter(request()->only('page', 'search', 'status', 'per_page'), fn ($v) => $v !== null && $v !== '');
@endphp

<div class="modal fade" id="truckEditModal" tabindex="-1" aria-labelledby="truckEditTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="#" enctype="multipart/form-data" autocomplete="off" class="modal-content border-0 shadow"
              data-confirm="ต้องการบันทึกการแก้ไขข้อมูลรถบรรทุกคันนี้หรือไม่" data-confirm-title="ยืนยันการแก้ไขข้อมูล"
              data-confirm-ok="บันทึกการแก้ไข" data-confirm-variant="primary">
            @csrf @method('PUT')
            <input type="hidden" name="_truck_id" data-edit-id>
            @foreach ($keepQuery as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="truckEditTitle">
                    แก้ไขรถบรรทุก <span class="text-muted fw-normal fs-6" data-edit-name></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body">
                @include('partials.modal-errors', ['bag' => $te])

                <div class="row g-4">
                    {{-- รูปรถ --}}
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold" for="te_image">รูปถ่ายรถบรรทุก</label>
                        <div class="border rounded bg-light d-flex align-items-center justify-content-center text-muted small mb-2"
                             style="height: 200px; overflow: hidden;">
                            <img alt="รูปรถ" class="d-none" style="max-height: 100%; max-width: 100%; object-fit: contain;" data-image-preview>
                            <span data-image-empty>ยังไม่มีรูป</span>
                        </div>
                        <input type="file" name="image" id="te_image" accept="image/jpeg,image/png,image/webp"
                               class="form-control {{ $bad('image') }}" data-image-input>
                        <div class="form-text">ไม่เลือกรูปใหม่ = ใช้รูปเดิม · JPG, PNG, WEBP ไม่เกิน 2MB</div>
                    </div>

                    {{-- ข้อมูลรถ --}}
                    <div class="col-lg-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="te_id_truck">เลขทะเบียน</label>
                                <input type="text" name="id_truck" id="te_id_truck" maxlength="7" inputmode="numeric"
                                       class="form-control {{ $bad('id_truck') }}" placeholder="เช่น 70-1234"
                                       required data-required-msg="กรุณาใส่เลขทะเบียน" data-plate>
                                <div class="form-text">ตัวเลข 2 หลัก + 4 หลัก ระบบใส่ขีดกลางให้</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="te_province_truck">จังหวัดที่จดทะเบียน</label>
                                <select name="province_truck" id="te_province_truck" class="form-select {{ $bad('province_truck') }}"
                                        required data-required-msg="กรุณาเลือกจังหวัด">
                                    <option value="">— เลือกจังหวัด —</option>
                                    @foreach (config('provinces') ?? [] as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="te_brand">ยี่ห้อ</label>
                                <select name="truck_brand_id" id="te_brand" class="form-select {{ $bad('truck_brand_id') }}"
                                        required data-required-msg="กรุณาเลือกยี่ห้อ" data-brand>
                                    <option value="">— เลือกยี่ห้อรถ —</option>
                                    @foreach ($brandOptions as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name_brand }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="te_model">รุ่น</label>
                                <select name="truck_model_id" id="te_model" class="form-select {{ $bad('truck_model_id') }}"
                                        required data-required-msg="กรุณาเลือกรุ่น" data-model>
                                    <option value="">— เลือกรุ่นรถ —</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="te_year">ปีที่ซื้อ</label>
                                <select name="year_truck" id="te_year" class="form-select {{ $bad('year_truck') }}"
                                        required data-required-msg="กรุณาเลือกปีที่ซื้อ" data-year>
                                    <option value="">— เลือกปีที่ซื้อ —</option>
                                </select>
                                <div class="form-text" data-year-hint>เลือกได้ตั้งแต่ปีผลิตเป็นต้นไป</div>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label" for="te_cubic">ความจุกระบะ (คิว)</label>
                                <input type="number" name="cubic_capacity" id="te_cubic" class="form-control bg-light" readonly tabindex="-1" data-spec="cubic_capacity">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label" for="te_weight">น้ำหนักรถเปล่า (กก.)</label>
                                <input type="number" name="weight_truck" id="te_weight" class="form-control bg-light" readonly tabindex="-1" data-spec="curb_weight">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label" for="te_tank">ถังน้ำมัน (ลิตร)</label>
                                <input type="number" name="fuelfactory_truck" id="te_tank" class="form-control bg-light" readonly tabindex="-1" data-spec="tank_capacity">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label" for="te_rate">อัตราสิ้นเปลือง (กม./ลิตร)</label>
                                <input type="number" name="fuel_rate" id="te_rate" class="form-control bg-light {{ $bad('fuel_rate') }}" readonly tabindex="-1" data-spec="fuel_rate">
                            </div>
                            <div class="col-12 form-text mt-1">สเปคดึงจากรุ่นรถอัตโนมัติ</div>

                            <div class="col-md-6">
                                <label class="form-label" for="te_status">สถานะ</label>
                                <select name="status_truck" id="te_status" class="form-select {{ $bad('status_truck') }}" required>
                                    @foreach (\App\Models\Truck::STATUS_LABELS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">เปลี่ยนสถานะพร้อมบันทึกประวัติการซ่อม ทำได้ที่หน้ารายละเอียดรถ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

@include('partials.modal-form')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('truckEditModal');
        const form    = modalEl.querySelector('form');
        const brands  = @json($brandOptions);
        const YEAR_MAX = {{ $ymax }};

        const brandSel = form.querySelector('[data-brand]');
        const modelSel = form.querySelector('[data-model]');
        const yearSel  = form.querySelector('[data-year]');
        const yearHint = form.querySelector('[data-year-hint]');
        const img      = form.querySelector('[data-image-preview]');

        const findModel = (id) => brands.flatMap((b) => b.models || []).find((m) => String(m.id) === String(id)) || null;

        function fillModels(selected) {
            modelSel.innerHTML = '<option value="">— เลือกรุ่นรถ —</option>';
            const brand = brands.find((b) => String(b.id) === String(brandSel.value));
            (brand?.models || []).forEach((m) => {
                const opt = new Option(m.model_year ? m.name_model + ' (' + m.model_year + ')' : m.name_model, m.id);
                modelSel.add(opt);
            });
            modelSel.value = selected || '';
            modelSel.disabled = !brandSel.value;
        }

        function fillYears(minYear, selected) {
            const min = parseInt(minYear) || 1980;
            yearSel.innerHTML = '<option value="">— เลือกปีที่ซื้อ —</option>';
            for (let y = YEAR_MAX; y >= min; y--) yearSel.add(new Option(y, y));
            yearSel.value = selected && selected >= min ? selected : '';
            yearHint.textContent = minYear
                ? 'รุ่นนี้ผลิตปี ' + minYear + ' เลือกได้ตั้งแต่ปี ' + minYear + ' ถึงปัจจุบัน'
                : 'เลือกได้ตั้งแต่ปีผลิตเป็นต้นไป';
        }

        function fillSpecs(keepYear) {
            const model = findModel(modelSel.value);
            form.querySelectorAll('[data-spec]').forEach((el) => {
                const v = model ? model[el.dataset.spec] : null;
                el.value = v === null || v === undefined ? '' : (['curb_weight', 'tank_capacity'].includes(el.dataset.spec) ? Math.round(v) : v);
            });
            fillYears(model?.model_year, keepYear ?? yearSel.value);
        }

        brandSel.addEventListener('change', () => { fillModels(''); fillSpecs(); });
        modelSel.addEventListener('change', () => fillSpecs());

        // เลขทะเบียน 70-1234
        const plate = form.querySelector('[data-plate]');
        plate.addEventListener('input', () => {
            const raw = plate.value.replace(/\D/g, '').slice(0, 6);
            plate.value = raw.length <= 2 ? raw : raw.slice(0, 2) + '-' + raw.slice(2);
            plate.setCustomValidity(raw.length === 6 ? '' : 'กรุณากรอกเลขทะเบียนให้ครบ เช่น 70-1234');
        });

        // รูปตัวอย่าง
        const showImage = (src) => {
            img.classList.toggle('d-none', !src);
            form.querySelector('[data-image-empty]').classList.toggle('d-none', !!src);
            if (src) img.src = src;
        };
        form.querySelector('[data-image-input]').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => showImage(ev.target.result);
            reader.readAsDataURL(file);
        });

        function open(truck, old) {
            if (!old) ModalForm.clearErrors(modalEl);
            const v = Object.assign({}, truck, old || {});

            modalEl.querySelector('[data-edit-name]').textContent = '— ' + truck.id_truck;
            form.querySelector('[data-edit-id]').value = truck.id_truck;
            form.action = truck.url;
            form.querySelector('[data-image-input]').value = '';
            showImage(truck.image);
            plate.setCustomValidity('');

            ModalForm.fill(form, {
                id_truck: v.id_truck, province_truck: v.province_truck,
                truck_brand_id: v.truck_brand_id, status_truck: v.status_truck,
            });
            fillModels(v.truck_model_id);
            fillSpecs(parseInt(v.year_truck) || '');
            ModalForm.open(modalEl);
        }

        document.querySelectorAll('[data-truck-edit]').forEach((btn) =>
            btn.addEventListener('click', () => open(JSON.parse(btn.dataset.truckEdit))));

        @if ($openEditTruck)
            open(@json($truckEditData($openEditTruck)), @json($oldEdit));
        @endif
    });
</script>
