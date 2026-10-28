@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขประเภทสินค้า</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">
        <form method="POST" action="{{ route('product_types.update', $product_type->id_product_type) }}"
            data-confirm="ต้องการบันทึกการแก้ไขประเภทสินค้านี้หรือไม่" data-confirm-title="ยืนยันการแก้ไขข้อมูล" data-confirm-ok="บันทึกการแก้ไข" data-confirm-variant="primary">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">ชื่อประเภทสินค้า</label>
                <input type="text" name="name_product_type"
                    class="form-control @error('name_product_type') is-invalid @enderror"
                    value="{{ old('name_product_type', $product_type->name_product_type) }}"
                    required data-required-msg="กรุณาใส่ประเภทสินค้า">
                @error('name_product_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-dark">บันทึก</button>
            <a href="{{ route('product_types.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
        </form>
    </div>
@endsection
