@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขผู้ใช้งาน</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        {{-- ============ ข้อมูลทั่วไป ============ --}}
        @if ($errors->updateUser->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>ไม่สามารถบันทึกข้อมูลผู้ใช้ได้</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->updateUser->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('users.update', $user) }}"
            data-confirm="ข้อมูลและสิทธิ์การเข้าถึงของ {{ $user->name }} จะถูกอัปเดตทันทีหลังบันทึก"
            data-confirm-title="ยืนยันการแก้ไขข้อมูลผู้ใช้"
            data-confirm-variant="primary"
            data-confirm-ok="บันทึก">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="255"
                    class="form-control @error('name', 'updateUser') is-invalid @enderror"
                    required data-required-msg="กรุณาใส่ชื่อ-นามสกุล">
                @error('name', 'updateUser')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">อีเมล (ใช้เข้าสู่ระบบ)</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255"
                    class="form-control @error('email', 'updateUser') is-invalid @enderror"
                    required data-required-msg="กรุณาใส่อีเมล">
                @error('email', 'updateUser')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">สิทธิ์การใช้งาน</label>
                <select name="role" class="form-select @error('role', 'updateUser') is-invalid @enderror"
                    required data-required-msg="กรุณาเลือกสิทธิ์การใช้งาน">
                    @foreach ($roles as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('role', $user->role) === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('role', 'updateUser')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- ============ สิทธิ์รายหน้า ============ --}}
            @include('users._permissions')

            <button class="btn btn-dark">บันทึกการแก้ไข</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>

        </form>

        <hr class="my-4">

        {{-- ============ ลายเซ็นอิเล็กทรอนิกส์ ============ --}}
        <h5 class="mb-1">ลายเซ็นอิเล็กทรอนิกส์</h5>
        <p class="text-muted small mb-3">แสดงในกรอบ "ผู้จัดทำ / ผู้อนุมัติ / ผู้รับเงิน" ของเอกสาร PDF ที่ผู้ใช้นี้ออก</p>

        @php
            $gdLoaded = extension_loaded('gd');
            $sigIsPng = $user->signature_path && str_ends_with(strtolower($user->signature_path), '.png');
        @endphp

        @unless ($gdLoaded)
            <div class="alert alert-warning small">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <strong>เว็บเซิร์ฟเวอร์ยังไม่ได้เปิด PHP GD</strong> — ลายเซ็นไฟล์ <b>PNG</b> จะไม่แสดงใน PDF
                (ไฟล์ <b>JPG</b> ใช้ได้เลย)
                <div class="mt-1">
                    วิธีแก้: เปิดไฟล์ <code>{{ php_ini_loaded_file() ?: 'php.ini' }}</code>
                    ลบ <code>;</code> หน้าบรรทัด <code>extension=gd</code> แล้วกด Stop / Start Apache ใน XAMPP Control Panel
                </div>
            </div>
        @endunless

        @if ($errors->updateSignature->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->updateSignature->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('users.signature', $user) }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-3 align-items-start">
                <div class="col-md-5">
                    <label class="form-label">ตำแหน่ง (พิมพ์ใต้ชื่อในเอกสาร)</label>
                    <input type="text" name="position" maxlength="100" class="form-control"
                           value="{{ old('position', $user->position) }}" placeholder="เช่น ผู้จัดการฝ่ายขาย">
                </div>

                <div class="col-md-4">
                    <label class="form-label">ไฟล์ลายเซ็น (PNG / JPG ไม่เกิน 1 MB)</label>
                    <input type="file" name="signature" accept=".png,.jpg,.jpeg" class="form-control">
                    <div class="form-text">แนะนำพื้นหลังขาว/โปร่งใส ขนาดประมาณ 400×150 px</div>
                </div>

                <div class="col-md-3">
                    <div class="form-label">ลายเซ็นปัจจุบัน</div>
                    @if ($user->hasSignature())
                        <div class="border rounded p-2 bg-white text-center">
                            <img src="{{ asset($user->signature_path) }}" alt="ลายเซ็น" style="max-height: 60px; max-width: 100%;">
                        </div>
                        @if ($sigIsPng && ! $gdLoaded)
                            <div class="small text-warning mt-1">ไฟล์ PNG — จะยังไม่แสดงใน PDF จนกว่าจะเปิด GD</div>
                        @endif
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="remove_signature" value="1" id="remove_signature">
                            <label class="form-check-label small" for="remove_signature">ลบลายเซ็นนี้</label>
                        </div>
                    @else
                        <div class="text-muted small">ยังไม่มีลายเซ็น</div>
                    @endif
                </div>
            </div>

            <button class="btn btn-outline-dark mt-3">
                <i class="bi bi-pen me-1"></i>บันทึกลายเซ็น
            </button>
        </form>

        <hr class="my-4">

        {{-- ============ รีเซ็ตรหัสผ่าน ============ --}}
        <h5 class="mb-3">รีเซ็ตรหัสผ่าน</h5>

        @if ($errors->updatePassword->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>ไม่สามารถรีเซ็ตรหัสผ่านได้</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->updatePassword->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('users.resetPassword', $user) }}"
            data-confirm="รหัสผ่านเดิมของ {{ $user->name }} จะใช้งานไม่ได้ทันที กรุณาแจ้งรหัสผ่านใหม่ให้ผู้ใช้ทราบ"
            data-confirm-title="ยืนยันการรีเซ็ตรหัสผ่าน"
            data-confirm-variant="danger"
            data-confirm-ok="รีเซ็ตรหัสผ่าน">
            @csrf @method('PATCH')

            <div class="mb-3">
                <label class="form-label">รหัสผ่านใหม่</label>
                <input type="password" name="password" id="new_password" minlength="8" autocomplete="new-password"
                    class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                    required data-required-msg="กรุณาใส่รหัสผ่านใหม่">
                <div class="form-text">อย่างน้อย 8 ตัวอักษร</div>
                @error('password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password"
                    required data-required-msg="กรุณายืนยันรหัสผ่านใหม่"
                    data-match="new_password" data-match-msg="รหัสผ่านทั้งสองช่องไม่ตรงกัน">
            </div>

            <button class="btn btn-outline-danger">รีเซ็ตรหัสผ่าน</button>

        </form>

    </div>
@endsection