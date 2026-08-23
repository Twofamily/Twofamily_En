@extends('layout')

@section('namepage')
    <div class="container">
        <h3>เพิ่มผู้ใช้งาน</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">อีเมล (ใช้เข้าสู่ระบบ)</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">สิทธิ์การใช้งาน</label>
                <select name="role" class="form-select @error('role') is-invalid @enderror">
                    <option value="">-- เลือกสิทธิ์ --</option>
                    @foreach ($roles as $key => $label)
                        <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password"
                    class="form-control @error('password') is-invalid @enderror">
                <div class="form-text">อย่างน้อย 8 ตัวอักษร</div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">ยืนยันรหัสผ่าน</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                    class="form-check-input" {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">เปิดใช้งานบัญชีทันที</label>
            </div>

            <button class="btn btn-dark">บันทึก</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>

        </form>

    </div>
@endsection