@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขผู้ใช้งาน</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        {{-- ============ ข้อมูลทั่วไป ============ --}}
        <form method="POST" action="{{ route('users.update', $user) }}"
            data-confirm="ข้อมูลและสิทธิ์การเข้าถึงของ {{ $user->name }} จะถูกอัปเดตทันทีหลังบันทึก"
            data-confirm-title="ยืนยันการแก้ไขข้อมูลผู้ใช้"
            data-confirm-variant="primary"
            data-confirm-ok="บันทึก">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                    class="form-control @error('name') is-invalid @enderror">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">อีเมล (ใช้เข้าสู่ระบบ)</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                    class="form-control @error('email') is-invalid @enderror">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">สิทธิ์การใช้งาน</label>
                <select name="role" class="form-select @error('role') is-invalid @enderror">
                    @foreach ($roles as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('role', $user->role) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-dark">บันทึกการแก้ไข</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>

        </form>

        <hr class="my-4">

        {{-- ============ รีเซ็ตรหัสผ่าน ============ --}}
        <h5 class="mb-3">รีเซ็ตรหัสผ่าน</h5>

        <form method="POST" action="{{ route('users.resetPassword', $user) }}"
            data-confirm="รหัสผ่านเดิมของ {{ $user->name }} จะใช้งานไม่ได้ทันที กรุณาแจ้งรหัสผ่านใหม่ให้ผู้ใช้ทราบ"
            data-confirm-title="ยืนยันการรีเซ็ตรหัสผ่าน"
            data-confirm-variant="danger"
            data-confirm-ok="รีเซ็ตรหัสผ่าน">
            @csrf @method('PATCH')

            <div class="mb-3">
                <label class="form-label">รหัสผ่านใหม่</label>
                <input type="password" name="password"
                    class="form-control @error('password') is-invalid @enderror">
                <div class="form-text">อย่างน้อย 8 ตัวอักษร</div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>

            <button class="btn btn-outline-danger">รีเซ็ตรหัสผ่าน</button>

        </form>

    </div>
@endsection