@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขสินค้า</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">
        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- ส่วนแสดงตัวอย่างและอัปโหลดรูปภาพ -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <label class="form-label fw-bold">รูปถ่ายสินค้า</label>

                    <div class="mb-3 text-center">
                        @if ($product->image)
                            <div id="preview_wrapper">
                                <img src="{{ asset('storage/' . $product->image) }}" id="product_preview"
                                    class="img-thumbnail rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
                            </div>
                            <div id="placeholder_box" class="p-4 border rounded text-muted bg-light d-none">
                                ยังไม่ได้เลือกรูปภาพ
                            </div>
                        @else
                            <div id="preview_wrapper" class="d-none">
                                <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                    id="product_preview" class="img-thumbnail rounded shadow-sm"
                                    style="max-height: 220px; object-fit: cover;">
                            </div>
                            <div id="placeholder_box" class="p-4 border rounded text-muted bg-light">
                                ยังไม่ได้เลือกรูปภาพ
                            </div>
                        @endif
                    </div>

                    <input type="file" name="image" id="image_input"
                        class="form-control @error('image') is-invalid @enderror"
                        accept="image/jpeg,image/png,image/webp">

                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted">รองรับไฟล์ JPG, PNG, WEBP ขนาดไม่เกิน 2MB (แนบไฟล์ใหม่หากต้องการเปลี่ยน)</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">ชื่อสินค้า</label>
                <input type="text" name="name_product" class="form-control @error('name_product') is-invalid @enderror"
                    value="{{ old('name_product', $product->name_product) }}" placeholder="เช่น หินกรวด" required>
                @error('name_product')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">รายละเอียด</label>
                <textarea name="detail_product" class="form-control @error('detail_product') is-invalid @enderror" rows="3"
                    placeholder="เช่น ใช้สำหรับงานก่อสร้าง หรือถนน">{{ old('detail_product', $product->detail_product) }}</textarea>
                @error('detail_product')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">ราคาต่อหน่วย (บาท / คิว)</label>
                <input type="number" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror"
                    value="{{ old('unit_price', $product->unit_price) }}" placeholder="เช่น 390" min="0" required>
                @error('unit_price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">ประเภทสินค้า</label>
                <select name="product_type_id" class="form-select @error('product_type_id') is-invalid @enderror">
                    <option value="">— เลือกประเภทสินค้า —</option>
                    @foreach ($types as $t)
                        <option value="{{ $t->id_product_type }}" @selected(old('product_type_id', $product->product_type_id) == $t->id_product_type)>
                            {{ $t->name_product_type }}
                        </option>
                    @endforeach
                </select>
                @error('product_type_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text text-muted">
                    เลือกประเภทของสินค้า เช่น ดิน, ทราย, หิน
                </div>
            </div>

            <button type="submit" class="btn btn-dark">อัปเดต</button>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('image_input');
            const imagePreview = document.getElementById('product_preview');
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
        });
    </script>
@endsection