<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">ยี่ห้อ</label>
        <select name="truck_brand_id" class="form-select @error('truck_brand_id') is-invalid @enderror" required>
            <option value="">— เลือกยี่ห้อ —</option>
            @foreach ($brands as $b)
                <option value="{{ $b->id }}" @selected(old('truck_brand_id', $model->truck_brand_id ?? '') == $b->id)>
                    {{ $b->name_brand }}
                </option>
            @endforeach
        </select>
        @error('truck_brand_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">ชื่อรุ่น</label>
        <input type="text" name="name_model" value="{{ old('name_model', $model->name_model ?? '') }}"
            class="form-control @error('name_model') is-invalid @enderror"
            placeholder="เช่น FVZ, Victor 500" required maxlength="100">
        @error('name_model')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">ปีรุ่น</label>
        <select name="model_year" class="form-select @error('model_year') is-invalid @enderror" required>
            <option value="">— เลือกปี —</option>
            @for ($y = now()->year + 1; $y >= 1980; $y--)
                <option value="{{ $y }}" @selected(old('model_year', $model->model_year ?? '') == $y)>{{ $y }}</option>
            @endfor
        </select>
        @error('model_year')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text text-muted">รุ่นเดียวกันคนละปี ให้แยกเป็นคนละรายการ</div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-3">
        <label class="form-label">ประเภท</label>
        <select name="truck_type" class="form-select">
            @foreach (['ดัมพ์', 'พ่วง', 'เทรลเลอร์', 'กระบะ'] as $t)
                <option value="{{ $t }}" @selected(old('truck_type', $model->truck_type ?? 'ดัมพ์') == $t)>{{ $t }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">จำนวนล้อ</label>
        <input type="number" name="wheels" min="4" max="24" step="2"
            value="{{ old('wheels', $model->wheels ?? '') }}" class="form-control" placeholder="10">
    </div>

    <div class="col-md-3">
        <label class="form-label">ความจุกระบะ (คิว)</label>
        <input type="number" name="cubic_capacity" min="0" max="100" step="0.1"
            value="{{ old('cubic_capacity', $model->cubic_capacity ?? '') }}" class="form-control" placeholder="10">
    </div>

    <div class="col-md-3">
        <label class="form-label">น้ำหนักบรรทุก (กก.)</label>
        <input type="number" name="load_capacity" min="0" step="1"
            value="{{ old('load_capacity', $model->load_capacity ?? '') }}" class="form-control" placeholder="16000">
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-3">
        <label class="form-label">น้ำหนักรถเปล่า (กก.)</label>
        <input type="number" name="curb_weight" min="0" step="1"
            value="{{ old('curb_weight', $model->curb_weight ?? '') }}" class="form-control" placeholder="9000">
    </div>

    <div class="col-md-3">
        <label class="form-label">ความจุถังน้ำมัน (ลิตร)</label>
        <input type="number" name="tank_capacity" min="0" max="1000" step="1"
            value="{{ old('tank_capacity', $model->tank_capacity ?? '') }}" class="form-control" placeholder="300">
    </div>

    <div class="col-md-3">
        <label class="form-label">อัตราสิ้นเปลือง (กม./ลิตร)</label>
        <input type="number" name="fuel_rate" min="0.1" max="50" step="0.01" required
            value="{{ old('fuel_rate', $model->fuel_rate ?? '') }}" class="form-control" placeholder="3.50">
        @error('fuel_rate')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">เชื้อเพลิง</label>
        <input type="text" name="fuel_type" maxlength="30"
            value="{{ old('fuel_type', $model->fuel_type ?? 'ดีเซล') }}" class="form-control">
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-3">
        <label class="form-label">ขนาดเครื่องยนต์ (ซีซี)</label>
        <input type="number" name="engine_cc" min="0" step="1"
            value="{{ old('engine_cc', $model->engine_cc ?? '') }}" class="form-control" placeholder="7790">
    </div>

    <div class="col-md-3">
        <label class="form-label">แรงม้า</label>
        <input type="number" name="horsepower" min="0" step="1"
            value="{{ old('horsepower', $model->horsepower ?? '') }}" class="form-control" placeholder="240">
    </div>

    <div class="col-md-3">
        <label class="form-label">สถานะ</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected(old('is_active', $model->is_active ?? 1) == 1)>เปิดใช้งาน</option>
            <option value="0" @selected(old('is_active', $model->is_active ?? 1) == 0)>ปิดใช้งาน</option>
        </select>
    </div>
</div>