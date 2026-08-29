@php($mode = $mode ?? 'create')
@php($ymax = now()->year)

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <label class="form-label fw-bold">รูปถ่ายรถบรรทุก</label>

        <!-- พื้ันที่แสดงตัวอย่างรูปภาพ (Preview) -->
        <div class="mb-3 text-center">
            @if (isset($truck) && $truck->image)
                <div id="preview_wrapper">
                    <img src="{{ asset('storage/' . $truck->image) }}" id="truck_preview"
                        class="img-thumbnail rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
                </div>
                <div id="placeholder_box" class="p-4 border rounded text-muted bg-light d-none">
                    ยังไม่ได้เลือกรูปภาพ
                </div>
            @else
                <div id="preview_wrapper" class="d-none">
                    <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                        id="truck_preview" class="img-thumbnail rounded shadow-sm"
                        style="max-height: 220px; object-fit: cover;">
                </div>
                <div id="placeholder_box" class="p-4 border rounded text-muted bg-light">
                    ยังไม่ได้เลือกรูปภาพ
                </div>
            @endif
        </div>

        <input type="file" name="image" id="image_input" class="form-control @error('image') is-invalid @enderror"
            accept="image/jpeg,image/png,image/webp">

        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text text-muted">รองรับไฟล์ JPG, PNG, WEBP ขนาดไม่เกิน 2MB</div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">เลขทะเบียน</label>
    <input type="text" name="id_truck" id="id_truck" value="{{ old('id_truck', $truck->id_truck ?? '') }}"
        class="form-control @error('id_truck') is-invalid @enderror" placeholder="เช่น 70-1234" required maxlength="7"
        inputmode="numeric">
    @error('id_truck')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text text-muted">ตัวเลข 2 หลัก + ตัวเลข 4 หลัก ระบบใส่ขีดกลางให้อัตโนมัติ</div>
</div>

<div class="mb-3">
    <label class="form-label">จังหวัดที่จดทะเบียน</label>
    <select name="province_truck" class="form-select @error('province_truck') is-invalid @enderror" required>
        <option value="">— เลือกจังหวัด —</option>
        @foreach ($provinces as $province)
            <option value="{{ $province }}" @selected(old('province_truck', $truck->province_truck ?? '') == $province)>
                {{ $province }}
            </option>
        @endforeach
    </select>
    @error('province_truck')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row g-3 mt-2">

    <div class="col-md-4">
        <label class="form-label">ยี่ห้อ</label>
        <select name="truck_brand_id" id="truck_brand_select"
            class="form-select @error('truck_brand_id') is-invalid @enderror" required>
            <option value="">— เลือกยี่ห้อรถ —</option>
            @if (isset($brands))
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected(old('truck_brand_id', $truck->truck_brand_id ?? '') == $brand->id)>
                        {{ $brand->name_brand }}
                    </option>
                @endforeach
            @endif
        </select>
        @error('truck_brand_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">รุ่น</label>
        <select name="truck_model_id" id="truck_model_select"
            class="form-select @error('truck_model_id') is-invalid @enderror" required disabled>
            <option value="">— เลือกรุ่นรถ —</option>
        </select>
        @error('truck_model_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text text-muted">เลือกยี่ห้อก่อน</div>
    </div>

    <div class="col-md-4">
        <label class="form-label">ปีที่ซื้อ</label>
        <select name="year_truck" id="year_truck_select" class="form-select @error('year_truck') is-invalid @enderror">
            <option value="">— เลือกปีที่ซื้อ —</option>
            @for ($y = $ymax; $y >= 1980; $y--)
                <option value="{{ $y }}" @selected(old('year_truck', $truck->year_truck ?? '') == $y)>
                    {{ $y }}
                </option>
            @endfor
        </select>
        @error('year_truck')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div id="year_truck_hint" class="form-text text-muted">เลือกได้ตั้งแต่ปีผลิตเป็นต้นไป</div>
    </div>

</div>

<div class="row g-3 mt-2">

    <div class="col-md-3">
        <label class="form-label">ความจุกระบะ (คิว)</label>
        <input type="number" name="cubic_capacity" id="spec_cubic"
            value="{{ old('cubic_capacity', $truck->cubic_capacity ?? '') }}"
            class="form-control bg-light @error('cubic_capacity') is-invalid @enderror" placeholder="รอเลือกรุ่นรถ..."
            readonly tabindex="-1">
        @error('cubic_capacity')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text text-muted">ดึงจากสเปครุ่นอัตโนมัติ</div>
    </div>

    <div class="col-md-3">
        <label class="form-label">น้ำหนักรถเปล่า (กก.)</label>
        <input type="number" name="weight_truck" id="spec_weight"
            value="{{ old('weight_truck', $truck->weight_truck ?? '') }}"
            class="form-control bg-light @error('weight_truck') is-invalid @enderror" placeholder="รอเลือกรุ่นรถ..."
            readonly tabindex="-1">
        @error('weight_truck')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">ความจุถังน้ำมัน (ลิตร)</label>
        <input type="number" name="fuelfactory_truck" id="spec_tank"
            value="{{ old('fuelfactory_truck', $truck->fuelfactory_truck ?? '') }}"
            class="form-control bg-light @error('fuelfactory_truck') is-invalid @enderror"
            placeholder="รอเลือกรุ่นรถ..." readonly tabindex="-1">
        @error('fuelfactory_truck')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">อัตราสิ้นเปลือง (กม./ลิตร)</label>
        <input type="number" name="fuel_rate" id="spec_rate"
            value="{{ old('fuel_rate', $truck->fuel_rate ?? '') }}"
            class="form-control bg-light @error('fuel_rate') is-invalid @enderror" placeholder="รอเลือกรุ่นรถ..."
            readonly tabindex="-1">
        @error('fuel_rate')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="mb-3 mt-4">
    <label class="form-label">สถานะ</label>
    @php($val = old('status_truck', $truck->status_truck ?? 'active'))
    <select name="status_truck" id="status_truck" class="form-select @error('status_truck') is-invalid @enderror"
        required>
        @foreach (\App\Models\Truck::STATUS_LABELS as $value => $label)
            <option value="{{ $value }}" @selected($val === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('status_truck')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if ($mode !== 'create')
        <div class="form-text text-muted">
            เปลี่ยนสถานะพร้อมบันทึกประวัติการซ่อมได้ที่หน้ารายละเอียดรถ
        </div>
    @endif
</div>

@if ($mode === 'create')
    <div id="maintenanceBox" class="card border-warning mb-4 d-none">
        <div class="card-body">
            <h6 class="mb-3">บันทึกการซ่อมของรถคันนี้</h6>

            <div class="mb-3">
                <label class="form-label">ซ่อมอะไร</label>
                <input type="text" name="title" value="{{ old('title') }}"
                    class="form-control @error('title') is-invalid @enderror" placeholder="เช่น เปลี่ยนยาง 6 เส้น">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">รายละเอียดเพิ่มเติม</label>
                <textarea name="detail" rows="2" class="form-control @error('detail') is-invalid @enderror">{{ old('detail') }}</textarea>
                @error('detail')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">วันที่เริ่มซ่อม</label>
                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}"
                        class="form-control @error('start_date') is-invalid @enderror">
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
{{-- 
                <div class="col-md-3">
                    <label class="form-label">คาดว่าเสร็จ</label>
                    <input type="date" name="expected_return" value="{{ old('expected_return') }}"
                        class="form-control @error('expected_return') is-invalid @enderror">
                    @error('expected_return')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> --}}

                <div class="col-md-3">
                    <label class="form-label">อู่ / ผู้ซ่อม</label>
                    <input type="text" name="garage" value="{{ old('garage') }}"
                        class="form-control @error('garage') is-invalid @enderror">
                    @error('garage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">ค่าซ่อมโดยประมาณ (บาท)</label>
                    <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost') }}"
                        class="form-control @error('cost') is-invalid @enderror">
                    @error('cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // 1. จัดรูปแบบเลขทะเบียน 70-1234
        const input = document.getElementById('id_truck');

        if (input) {
            const MAX_FRONT = 2;
            const MAX_BACK = 4;
            const MAX_TOTAL = MAX_FRONT + MAX_BACK;

            const clean = (v) => v.replace(/\D/g, '').slice(0, MAX_TOTAL);

            input.addEventListener('input', function() {
                const raw = clean(input.value);

                input.value = raw.length <= MAX_FRONT ?
                    raw :
                    raw.slice(0, MAX_FRONT) + '-' + raw.slice(MAX_FRONT);

                input.setCustomValidity(
                    raw.length === MAX_TOTAL ? '' : 'กรุณากรอกเลขทะเบียนให้ครบ เช่น 70-1234'
                );
            });
        }

        // 2. ตัวอย่างรูปภาพ (Image Preview)
        const imageInput = document.getElementById('image_input');
        const imagePreview = document.getElementById('truck_preview');
        const previewWrapper = document.getElementById('preview_wrapper');
        const placeholderBox = document.getElementById('placeholder_box');

        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        if (previewWrapper) previewWrapper.classList.remove('d-none');
                        if (placeholderBox) placeholderBox.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // 3. ยี่ห้อ -> รุ่น -> สเปคอัตโนมัติ และกรองปีที่ซื้อ
        @if (isset($brands))
            const brandsData = @json($brands);
            const brandSelect = document.getElementById('truck_brand_select');
            const modelSelect = document.getElementById('truck_model_select');
            const yearSelect = document.getElementById('year_truck_select');
            const yearHint = document.getElementById('year_truck_hint');

            const oldModelId = "{{ old('truck_model_id', $truck->truck_model_id ?? '') }}";
            const oldYearTruck = "{{ old('year_truck', $truck->year_truck ?? '') }}";
            const currentYear = {{ $ymax }};

            const specFields = {
                cubic_capacity: document.getElementById('spec_cubic'),
                curb_weight: document.getElementById('spec_weight'),
                tank_capacity: document.getElementById('spec_tank'),
                fuel_rate: document.getElementById('spec_rate'),
            };

            function updateModels() {
                const brandId = brandSelect.value;
                modelSelect.innerHTML = '<option value="">— เลือกรุ่นรถ —</option>';

                if (!brandId) {
                    modelSelect.disabled = true;
                    filterYearOptions(1980);
                    return;
                }

                modelSelect.disabled = false;
                const brand = brandsData.find(b => b.id == brandId);

                if (brand && brand.models) {
                    brand.models.forEach(model => {
                        const label = model.model_year ?
                            model.name_model + ' (' + model.model_year + ')' :
                            model.name_model;

                        const opt = new Option(label, model.id);
                        if (model.id == oldModelId) opt.selected = true;
                        modelSelect.add(opt);
                    });
                }
            }

            function findModel(modelId) {
                let found = null;
                brandsData.forEach(b => {
                    (b.models || []).forEach(m => {
                        if (m.id == modelId) found = m;
                    });
                });
                return found;
            }

            function filterYearOptions(minYear) {
                const selectedVal = yearSelect.value || oldYearTruck;
                yearSelect.innerHTML = '<option value="">— เลือกปีที่ซื้อ —</option>';

                const min = minYear ? parseInt(minYear) : 1980;

                for (let y = currentYear; y >= min; y--) {
                    const opt = new Option(y, y);
                    if (y == selectedVal) opt.selected = true;
                    yearSelect.add(opt);
                }

                if (yearHint) {
                    yearHint.textContent = minYear ?
                        `เลือกรุ่นรถแล้ว (ปีผลิต: ${minYear}) เลือกได้ตั้งแต่ปี ${minYear} ถึงปัจจุบัน` :
                        'เลือกได้ตั้งแต่ปีผลิตเป็นต้นไป';
                }
            }

            const intFields = ['curb_weight', 'tank_capacity'];

            function fillSpecs() {
                const model = findModel(modelSelect.value);

                if (!model) {
                    // เคลียร์ค่าว่างในฟิลด์สเปคกรณีที่ไม่ได้เลือกรุ่น หรือยกเลิกการเลือก
                    Object.keys(specFields).forEach(key => {
                        if (specFields[key]) specFields[key].value = '';
                    });
                    filterYearOptions(1980);
                    return;
                }

                Object.keys(specFields).forEach(key => {
                    const el = specFields[key];
                    if (el && model[key] !== null && model[key] !== undefined) {
                        el.value = intFields.includes(key) ?
                            Math.round(model[key]) :
                            model[key];
                    } else if (el) {
                        el.value = '';
                    }
                });

                if (model.model_year) {
                    filterYearOptions(model.model_year);
                } else {
                    filterYearOptions(1980);
                }
            }

            brandSelect.addEventListener('change', function() {
                updateModels();
                fillSpecs();
            });

            modelSelect.addEventListener('change', fillSpecs);

            if (brandSelect.value) {
                updateModels();
                fillSpecs();
            }
        @endif

        // 4. สลับกล่องซ่อมบำรุงตามสถานะ
        const statusSelect = document.getElementById('status_truck');
        const maintenanceBox = document.getElementById('maintenanceBox');

        if (statusSelect && maintenanceBox) {
            const toggleMaintenance = function() {
                maintenanceBox.classList.toggle('d-none', statusSelect.value !== 'maintenance');
            };

            statusSelect.addEventListener('change', toggleMaintenance);
            toggleMaintenance();
        }
    });
</script>
