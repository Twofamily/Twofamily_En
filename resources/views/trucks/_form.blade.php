@php($mode = $mode ?? 'create')
@php($ymax = now()->year)

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
        <label>ยี่ห้อ</label>
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
        <label>รุ่น</label>
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
        <label>ปีที่ซื้อ</label>
        <select name="year_truck" class="form-select @error('year_truck') is-invalid @enderror">
            <option value="">— เลือกปี —</option>
            @for ($y = $ymax; $y >= 1980; $y--)
                <option value="{{ $y }}" @selected(old('year_truck', $truck->year_truck ?? '') == $y)>
                    {{ $y }}
                </option>
            @endfor
        </select>
        @error('year_truck')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="row g-3 mt-2">

    <div class="col-md-3">
        <label>ความจุกระบะ (คิว)</label>
        <input type="number" name="cubic_capacity" id="spec_cubic" min="0" max="100" step="0.1"
            value="{{ old('cubic_capacity', $truck->cubic_capacity ?? '') }}"
            class="form-control @error('cubic_capacity') is-invalid @enderror" placeholder="เช่น 10">
        @error('cubic_capacity')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text text-muted">ใช้คิดค่าขนส่งต่อเที่ยว</div>
    </div>

    <div class="col-md-3">
        <label>น้ำหนักรถเปล่า (กก.)</label>
        <input type="number" name="weight_truck" id="spec_weight" min="0" max="50000" step="1"
            value="{{ old('weight_truck', $truck->weight_truck ?? '') }}"
            class="form-control @error('weight_truck') is-invalid @enderror" placeholder="เช่น 9000">
        @error('weight_truck')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label>ความจุถังน้ำมัน (ลิตร)</label>
        <input type="number" name="fuelfactory_truck" id="spec_tank" min="0" max="1000" step="1"
            value="{{ old('fuelfactory_truck', $truck->fuelfactory_truck ?? '') }}"
            class="form-control @error('fuelfactory_truck') is-invalid @enderror" placeholder="เช่น 300">
        @error('fuelfactory_truck')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label>อัตราสิ้นเปลือง (กม./ลิตร)</label>
        <input type="number" name="fuel_rate" id="spec_rate" min="0.1" max="50" step="0.01" required
            value="{{ old('fuel_rate', $truck->fuel_rate ?? '') }}"
            class="form-control @error('fuel_rate') is-invalid @enderror" placeholder="เช่น 3.5">
        @error('fuel_rate')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>

{{-- <div id="specHint" class="alert alert-info py-2 mt-2 d-none">
    <small>สเปคด้านบนถูกเติมอัตโนมัติจากรุ่นที่เลือก แก้ไขได้หากรถคันนี้ต่างจากมาตรฐาน</small>
</div> --}}

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
                    <label>วันที่เริ่มซ่อม</label>
                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}"
                        class="form-control @error('start_date') is-invalid @enderror">
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label>คาดว่าเสร็จ</label>
                    <input type="date" name="expected_return" value="{{ old('expected_return') }}"
                        class="form-control @error('expected_return') is-invalid @enderror">
                    @error('expected_return')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label>อู่ / ผู้ซ่อม</label>
                    <input type="text" name="garage" value="{{ old('garage') }}"
                        class="form-control @error('garage') is-invalid @enderror">
                    @error('garage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label>ค่าซ่อมโดยประมาณ (บาท)</label>
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

        // จัดรูปแบบเลขทะเบียน 70-1234
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

        // ยี่ห้อ → รุ่น → สเปคอัตโนมัติ
        @if (isset($brands))
            const brandsData = @json($brands);
            const brandSelect = document.getElementById('truck_brand_select');
            const modelSelect = document.getElementById('truck_model_select');

            const oldModelId = "{{ old('truck_model_id', $truck->truck_model_id ?? '') }}";

            // แมปคอลัมน์ในตาราง truck_models → ช่องกรอกในฟอร์ม
            const specFields = {
                cubic_capacity: document.getElementById('spec_cubic'),
                curb_weight: document.getElementById('spec_weight'),
                tank_capacity: document.getElementById('spec_tank'),
                fuel_rate: document.getElementById('spec_rate'),
            };
            const specHint = document.getElementById('specHint');

            function updateModels() {
                const brandId = brandSelect.value;

                modelSelect.innerHTML = '<option value="">— เลือกรุ่นรถ —</option>';

                if (!brandId) {
                    modelSelect.disabled = true;
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

            // ช่องที่ต้องเป็นจำนวนเต็ม
            const intFields = ['curb_weight', 'tank_capacity'];

            function fillSpecs() {
                const model = findModel(modelSelect.value);

                if (!model) {
                    if (specHint) specHint.classList.add('d-none');
                    return;
                }

                Object.keys(specFields).forEach(key => {
                    const el = specFields[key];
                    if (el && model[key] !== null && model[key] !== undefined) {
                        el.value = intFields.includes(key) ?
                            Math.round(model[key]) :
                            model[key];
                    }
                });

                if (specHint) specHint.classList.remove('d-none');
            }

            brandSelect.addEventListener('change', function() {
                updateModels();
                fillSpecs();
            });

            modelSelect.addEventListener('change', fillSpecs);

            if (brandSelect.value) updateModels();
        @endif

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
