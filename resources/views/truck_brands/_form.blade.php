<div class="mb-3">
    <label class="form-label">ชื่อยี่ห้อ</label>
    <input type="text" name="name_brand" value="{{ old('name_brand', $brand->name_brand ?? '') }}"
        class="form-control @error('name_brand') is-invalid @enderror"
        placeholder="เช่น ISUZU, HINO, FUSO" required maxlength="100">
    @error('name_brand')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>