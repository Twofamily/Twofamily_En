@extends('layout')

@section('namepage')
    <div class="container">
        <h3>จัดการผู้ใช้งาน</h3>
    </div>
@endsection

@php
    /*
     | เพิ่ม / แก้ไขผู้ใช้ทำในหน้าต่าง (modal) ของหน้านี้ ไม่ต้องย้ายหน้า
     | กรอกผิดแล้วเด้งกลับมา → เปิดหน้าต่างเดิมให้อัตโนมัติ พร้อมค่าที่กรอกไว้และข้อความแจ้ง
     */
    $roles = \App\Models\User::roleList();

    $createHasOld = $errors->createUser->any();          // กรอกผิดแล้วเด้งกลับ → ใช้ค่าที่กรอกไว้
    $openCreate   = $createHasOld || request('modal') === 'create';

    $editBag = match (true) {
        $errors->updateUser->any()      => 'info',
        $errors->updateSignature->any() => 'signature',
        $errors->updatePassword->any()  => 'password',
        default                         => null,
    };
    $openEditId  = $editBag ? (int) old('_user_id') : (int) request('edit');
    $openEditTab = $editBag ?? 'info';

    // ค่าที่กรอกไว้ก่อนเด้งกลับ (เฉพาะแท็บข้อมูลผู้ใช้ / ลายเซ็น)
    $oldEdit = match ($editBag) {
        'info'      => old() ? array_intersect_key(old(), array_flip(['first_name', 'last_name', 'email', 'role', 'permissions'])) : null,
        'signature' => ['position' => old('position')],
        default     => null,
    };

    $gdLoaded = extension_loaded('gd');

    // ข้อมูลที่หน้าต่างแก้ไขใช้เติมฟอร์มของผู้ใช้แต่ละคน
    $editDataOf = fn (\App\Models\User $u) => [
        'id'          => $u->id,
        'first_name'  => $u->first_name ?: \App\Models\User::splitName($u->name)[0],
        'last_name'   => $u->last_name ?? \App\Models\User::splitName($u->name)[1],
        'email'       => $u->email,
        'role'        => $u->role,
        'permissions' => $u->permissions ?? [],
        'position'    => $u->position,
        'signature'   => $u->hasSignature() ? asset($u->signature_path) : null,
        'sigIsPng'    => $u->signature_path && str_ends_with(strtolower($u->signature_path), '.png'),
        'isSelf'      => $u->id === auth()->id(),
        'urls'        => [
            'update'    => route('users.update', $u),
            'signature' => route('users.signature', $u),
            'password'  => route('users.resetPassword', $u),
        ],
    ];

    // ผู้ใช้ที่ต้องเปิดหน้าต่างแก้ไขให้ทันที (อาจอยู่คนละหน้าของตาราง จึงดึงแยก)
    $openEditUser = $openEditId ? \App\Models\User::find($openEditId) : null;
@endphp

@section('content')
    <div class="container py-3">

        <x-search-bar :action="route('users.index')"
                      placeholder="ค้นหาชื่อ / อีเมล / สิทธิ์การใช้งาน"
                      :search="$search" :per-page="$perPage"
                      :statuses="$statusLabels" :status="$status" status-label="สถานะบัญชี">
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label small text-muted mb-1">สิทธิ์การใช้งาน</label>
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">— ทุกสิทธิ์ —</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </x-search-bar>

        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-dark text-nowrap" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มผู้ใช้งาน
            </button>
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อ</th>
                        <th>นามสกุล</th>
                        <th>สิทธิ์การใช้งาน</th>
                        <th>สถานะ</th>
                        <th class="text-center" style="width:80px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        @php
                            $isSelf = $u->id === auth()->id();
                            $editData = $editDataOf($u);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $editData['first_name'] }}
                                    @if ($isSelf)
                                        <span class="badge bg-secondary ms-1">คุณ</span>
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-envelope me-1"></i>{{ $u->email }}
                                </div>
                            </td>

                            <td>{{ $editData['last_name'] ?: '-' }}</td>

                            <td>
                                {{ $u->role_name }}
                                @if ($u->position)
                                    <div class="small text-muted">{{ $u->position }}</div>
                                @endif
                            </td>

                            <td>
                                @if ($u->is_active)
                                    <span class="badge bg-success">ใช้งานอยู่</span>
                                @else
                                    <span class="badge bg-danger">ถูกระงับ</span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{-- ระงับและลบ ไม่แสดงในแถวของตนเอง --}}
                                <x-row-actions :delete="$isSelf ? null : route('users.destroy', $u)"
                                               :label="$u->name"
                                               delete-label="ลบบัญชี"
                                               :delete-confirm="'บัญชี ' . $u->name . ' (' . $u->email . ') จะถูกลบออกจากระบบ'"
                                               delete-title="ยืนยันการลบผู้ใช้งาน" delete-ok="ลบบัญชี">
                                    <li>
                                        <button type="button" class="dropdown-item" data-user-edit='@json($editData)'>
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>แก้ไข
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-user-edit='@json($editData)' data-user-tab="signature">
                                            <i class="bi bi-pen" aria-hidden="true"></i>ลายเซ็น
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-user-edit='@json($editData)' data-user-tab="password">
                                            <i class="bi bi-key" aria-hidden="true"></i>รีเซ็ตรหัสผ่าน
                                        </button>
                                    </li>
                                    @unless ($isSelf)
                                        <x-row-actions.form :action="route('users.toggleStatus', $u)" method="PATCH"
                                                            :icon="$u->is_active ? 'bi-person-x' : 'bi-person-check'"
                                                            :variant="$u->is_active ? 'warning' : 'success'"
                                                            :confirm="$u->is_active
                                                                ? $u->name . ' จะไม่สามารถเข้าสู่ระบบได้ทันที จนกว่าจะเปิดใช้งานอีกครั้ง'
                                                                : $u->name . ' จะสามารถเข้าสู่ระบบได้ทันที'"
                                                            :confirm-title="$u->is_active ? 'ยืนยันการระงับบัญชี' : 'ยืนยันการเปิดใช้งานบัญชี'"
                                                            :confirm-ok="$u->is_active ? 'ระงับบัญชี' : 'เปิดใช้งาน'">
                                            {{ $u->is_active ? 'ระงับบัญชี' : 'เปิดใช้งานบัญชี' }}
                                        </x-row-actions.form>
                                    @endunless
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ไม่พบข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            @include('partials.pagination-footer', ['paginator' => $users])
        </div>

    </div>

    {{-- ==================== หน้าต่างเพิ่มผู้ใช้ ==================== --}}
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('users.store') }}"
                  data-confirm="ต้องการสร้างบัญชีผู้ใช้งานนี้หรือไม่" data-confirm-title="ยืนยันการบันทึกข้อมูล"
                  data-confirm-ok="บันทึก" data-confirm-variant="primary">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserTitle">
                        <i class="bi bi-person-plus me-1"></i>เพิ่มผู้ใช้งาน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>

                <div class="modal-body">
                    @if ($errors->createUser->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 small">
                                @foreach ($errors->createUser->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php $ce = $errors->createUser; @endphp

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="c_first_name">ชื่อ <span class="req-star">*</span></label>
                            <input type="text" id="c_first_name" name="first_name" maxlength="100"
                                   value="{{ $createHasOld ? old('first_name') : '' }}"
                                   class="form-control {{ $ce->has('first_name') ? 'is-invalid' : '' }}"
                                   placeholder="เช่น สมชาย" required data-required-msg="กรุณาใส่ชื่อ">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c_last_name">นามสกุล <span class="req-star">*</span></label>
                            <input type="text" id="c_last_name" name="last_name" maxlength="100"
                                   value="{{ $createHasOld ? old('last_name') : '' }}"
                                   class="form-control {{ $ce->has('last_name') ? 'is-invalid' : '' }}"
                                   placeholder="เช่น ใจดี" required data-required-msg="กรุณาใส่นามสกุล">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="c_email">อีเมล (ใช้เข้าสู่ระบบ) <span class="req-star">*</span></label>
                            <input type="email" id="c_email" name="email" maxlength="255"
                                   value="{{ $createHasOld ? old('email') : '' }}"
                                   class="form-control {{ $ce->has('email') ? 'is-invalid' : '' }}"
                                   placeholder="เช่น somchai@twofamily.co.th" required data-required-msg="กรุณาใส่อีเมล">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c_role">สิทธิ์การใช้งาน <span class="req-star">*</span></label>
                            <select id="c_role" name="role" class="form-select {{ $ce->has('role') ? 'is-invalid' : '' }}"
                                    required data-required-msg="กรุณาเลือกสิทธิ์การใช้งาน">
                                @foreach ($roles as $key => $label)
                                    <option value="{{ $key }}" @selected(($createHasOld ? old('role') : 'staff') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="c_password">รหัสผ่าน <span class="req-star">*</span></label>
                            <input type="password" id="c_password" name="password" minlength="8" autocomplete="new-password"
                                   class="form-control {{ $ce->has('password') ? 'is-invalid' : '' }}"
                                   required data-required-msg="กรุณาตั้งรหัสผ่าน">
                            <div class="form-text">อย่างน้อย 8 ตัวอักษร</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c_password_confirmation">ยืนยันรหัสผ่าน <span class="req-star">*</span></label>
                            <input type="password" id="c_password_confirmation" name="password_confirmation" autocomplete="new-password"
                                   class="form-control" required data-required-msg="กรุณายืนยันรหัสผ่าน"
                                   data-match="c_password" data-match-msg="รหัสผ่านทั้งสองช่องไม่ตรงกัน">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" id="c_is_active" class="form-check-input"
                                       @checked($createHasOld ? old('is_active') : true)>
                                <label class="form-check-label" for="c_is_active">เปิดใช้งานบัญชีทันที</label>
                            </div>
                        </div>

                        <div class="col-12">
                            @include('users._permissions', [
                                'savedPerms'  => $createHasOld ? old('permissions', []) : [],
                                'currentRole' => $createHasOld ? old('role', 'staff') : 'staff',
                            ])
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <span class="small text-muted me-auto"><span class="req-star">*</span> = จำเป็นต้องกรอก</span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== หน้าต่างแก้ไขผู้ใช้ (ใช้ร่วมทุกแถว) ==================== --}}
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserTitle">
                        <i class="bi bi-person-gear me-1"></i>แก้ไขผู้ใช้งาน
                        <span class="text-muted fw-normal fs-6" data-edit-name></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>

                <div class="px-3 pt-3">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#editTabInfo" type="button" role="tab" data-tab="info">
                                <i class="bi bi-person me-1"></i>ข้อมูลผู้ใช้
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#editTabSignature" type="button" role="tab" data-tab="signature">
                                <i class="bi bi-pen me-1"></i>ลายเซ็น
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#editTabPassword" type="button" role="tab" data-tab="password">
                                <i class="bi bi-key me-1"></i>รีเซ็ตรหัสผ่าน
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content">
                    {{-- ---------- แท็บ 1: ข้อมูลผู้ใช้ ---------- --}}
                    <div class="tab-pane fade" id="editTabInfo" role="tabpanel">
                        <form method="POST" action="#" data-edit-form="update"
                              data-confirm="ข้อมูลและสิทธิ์การเข้าถึงจะถูกอัปเดตทันทีหลังบันทึก"
                              data-confirm-title="ยืนยันการแก้ไขข้อมูลผู้ใช้" data-confirm-variant="primary" data-confirm-ok="บันทึก">
                            @csrf @method('PUT')
                            <input type="hidden" name="_user_id" data-edit-id>

                            <div class="modal-body">
                                @if ($errors->updateUser->any())
                                    <div class="alert alert-danger py-2">
                                        <ul class="mb-0 small">
                                            @foreach ($errors->updateUser->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="e_first_name">ชื่อ <span class="req-star">*</span></label>
                                        <input type="text" id="e_first_name" name="first_name" maxlength="100" class="form-control"
                                               required data-required-msg="กรุณาใส่ชื่อ">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="e_last_name">นามสกุล <span class="req-star">*</span></label>
                                        <input type="text" id="e_last_name" name="last_name" maxlength="100" class="form-control"
                                               required data-required-msg="กรุณาใส่นามสกุล">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="e_email">อีเมล (ใช้เข้าสู่ระบบ) <span class="req-star">*</span></label>
                                        <input type="email" id="e_email" name="email" maxlength="255" class="form-control"
                                               required data-required-msg="กรุณาใส่อีเมล">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="e_role">สิทธิ์การใช้งาน <span class="req-star">*</span></label>
                                        <select id="e_role" name="role" class="form-select" required data-required-msg="กรุณาเลือกสิทธิ์การใช้งาน">
                                            @foreach ($roles as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text d-none" data-edit-self-note>
                                            บัญชีของคุณเอง — ถ้าเป็นผู้ดูแลระบบคนสุดท้าย จะลดสิทธิ์ตัวเองไม่ได้
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        @include('users._permissions')
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
                            </div>
                        </form>
                    </div>

                    {{-- ---------- แท็บ 2: ลายเซ็น ---------- --}}
                    <div class="tab-pane fade" id="editTabSignature" role="tabpanel">
                        <form method="POST" action="#" enctype="multipart/form-data" data-edit-form="signature">
                            @csrf
                            <input type="hidden" name="_user_id" data-edit-id>

                            <div class="modal-body">
                                <p class="text-muted small">แสดงในกรอบ "ผู้จัดทำ / ผู้อนุมัติ / ผู้รับเงิน" ของเอกสาร PDF ที่ผู้ใช้นี้ออก</p>

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
                                    <div class="alert alert-danger py-2">
                                        <ul class="mb-0 small">
                                            @foreach ($errors->updateSignature->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="e_position">ตำแหน่ง (พิมพ์ใต้ชื่อในเอกสาร)</label>
                                        <input type="text" id="e_position" name="position" maxlength="100" class="form-control"
                                               placeholder="เช่น ผู้จัดการฝ่ายขาย">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="e_signature">ไฟล์ลายเซ็น (PNG / JPG ไม่เกิน 1 MB)</label>
                                        <input type="file" id="e_signature" name="signature" accept=".png,.jpg,.jpeg" class="form-control">
                                        <div class="form-text">แนะนำพื้นหลังขาว/โปร่งใส ขนาดประมาณ 400×150 px</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-label">ลายเซ็นปัจจุบัน</div>
                                        <div data-sig-current class="d-none">
                                            <div class="border rounded p-2 bg-white text-center" style="max-width: 320px;">
                                                <img data-sig-img alt="ลายเซ็น" style="max-height: 70px; max-width: 100%;">
                                            </div>
                                            @unless ($gdLoaded)
                                                <div class="small text-warning mt-1 d-none" data-sig-png-warn>
                                                    ไฟล์ PNG — จะยังไม่แสดงใน PDF จนกว่าจะเปิด GD
                                                </div>
                                            @endunless
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" name="remove_signature" value="1" id="e_remove_signature">
                                                <label class="form-check-label small" for="e_remove_signature">ลบลายเซ็นนี้</label>
                                            </div>
                                        </div>
                                        <div data-sig-none class="text-muted small">ยังไม่มีลายเซ็น</div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-dark"><i class="bi bi-pen me-1"></i>บันทึกลายเซ็น</button>
                            </div>
                        </form>
                    </div>

                    {{-- ---------- แท็บ 3: รีเซ็ตรหัสผ่าน ---------- --}}
                    <div class="tab-pane fade" id="editTabPassword" role="tabpanel">
                        <form method="POST" action="#" data-edit-form="password"
                              data-confirm="รหัสผ่านเดิมจะใช้งานไม่ได้ทันที กรุณาแจ้งรหัสผ่านใหม่ให้ผู้ใช้ทราบ"
                              data-confirm-title="ยืนยันการรีเซ็ตรหัสผ่าน" data-confirm-variant="danger" data-confirm-ok="รีเซ็ตรหัสผ่าน">
                            @csrf @method('PATCH')
                            <input type="hidden" name="_user_id" data-edit-id>

                            <div class="modal-body">
                                @if ($errors->updatePassword->any())
                                    <div class="alert alert-danger py-2">
                                        <ul class="mb-0 small">
                                            @foreach ($errors->updatePassword->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="e_new_password">รหัสผ่านใหม่ <span class="req-star">*</span></label>
                                        <input type="password" id="e_new_password" name="password" minlength="8" autocomplete="new-password"
                                               class="form-control" required data-required-msg="กรุณาใส่รหัสผ่านใหม่">
                                        <div class="form-text">อย่างน้อย 8 ตัวอักษร</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="e_new_password_confirmation">ยืนยันรหัสผ่านใหม่ <span class="req-star">*</span></label>
                                        <input type="password" id="e_new_password_confirmation" name="password_confirmation" autocomplete="new-password"
                                               class="form-control" required data-required-msg="กรุณายืนยันรหัสผ่านใหม่"
                                               data-match="e_new_password" data-match-msg="รหัสผ่านทั้งสองช่องไม่ตรงกัน">
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-key me-1"></i>รีเซ็ตรหัสผ่าน</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModalEl = document.getElementById('editUserModal');
            const editModal   = bootstrap.Modal.getOrCreateInstance(editModalEl);
            const $ = (sel) => editModalEl.querySelector(sel);

            // เติมฟอร์มทั้ง 3 แท็บด้วยข้อมูลของผู้ใช้คนนั้น
            function fill(user, old) {
                const v = Object.assign({}, user, old || {});

                $('[data-edit-name]').textContent = '— ' + (user.first_name + ' ' + (user.last_name || '')).trim();
                editModalEl.querySelectorAll('[data-edit-id]').forEach((el) => el.value = user.id);
                $('[data-edit-form="update"]').action    = user.urls.update;
                $('[data-edit-form="signature"]').action = user.urls.signature;
                $('[data-edit-form="password"]').action  = user.urls.password;

                $('#e_first_name').value = v.first_name || '';
                $('#e_last_name').value  = v.last_name || '';
                $('#e_email').value      = v.email || '';
                $('#e_role').value       = v.role || 'staff';
                $('[data-edit-self-note]').classList.toggle('d-none', !user.isSelf);
                window.PermissionBox.set($('[data-edit-form="update"] [data-perm-box]'), v.permissions || {}, v.role);

                $('#e_position').value = (old && 'position' in old ? old.position : user.position) || '';
                $('#e_signature').value = '';
                $('#e_remove_signature').checked = false;
                $('[data-sig-current]').classList.toggle('d-none', !user.signature);
                $('[data-sig-none]').classList.toggle('d-none', !!user.signature);
                if (user.signature) $('[data-sig-img]').src = user.signature;
                const pngWarn = $('[data-sig-png-warn]');
                if (pngWarn) pngWarn.classList.toggle('d-none', !user.sigIsPng);

                $('#e_new_password').value = '';
                $('#e_new_password_confirmation').value = '';
            }

            function open(user, tab, old) {
                fill(user, old);
                const tabBtn = $('[data-tab="' + (tab || 'info') + '"]');
                bootstrap.Tab.getOrCreateInstance(tabBtn).show();
                editModal.show();
            }

            document.querySelectorAll('[data-user-edit]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    open(JSON.parse(btn.dataset.userEdit), btn.dataset.userTab);
                });
            });

            // เปิดหน้าต่างให้เองเมื่อกรอกผิดแล้วเด้งกลับ หรือมาจากลิงก์ ?modal=create / ?edit=ID
            @if ($openCreate)
                bootstrap.Modal.getOrCreateInstance(document.getElementById('createUserModal')).show();
            @elseif ($openEditUser)
                open(@json($editDataOf($openEditUser)), @json($openEditTab), @json($oldEdit));
            @endif
        });
    </script>
@endsection
