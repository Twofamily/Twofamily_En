{{--
    ตารางกำหนดสิทธิ์รายหน้า
    วางไว้ "ข้างใน <form>" เดียวกับช่องเลือก role ในหน้า users/create และ users/edit
    ใช้:  @include('users._permissions')
--}}
@php
    $permUser    = $user ?? null;
    $savedPerms  = old('permissions', $permUser?->permissions ?? []);
    $currentRole = old('role', $permUser?->role ?? \App\Models\User::ROLE_STAFF);
    $levelLabels = \App\Support\Permission::LEVEL_LABELS;
@endphp

<div class="mb-3" id="permissionBox">
    <label class="form-label fw-semibold">สิทธิ์การเข้าใช้งานแต่ละหน้า</label>
    <div class="form-text text-muted mt-0 mb-2">
        หน้าที่ตั้งเป็น "ไม่มีสิทธิ์" จะถูกซ่อนจากเมนูและเปิดผ่านลิงก์ตรงไม่ได้
        ผู้ดูข้อมูล (viewer) เลือกได้สูงสุดแค่ "ดูอย่างเดียว"
    </div>

    <div class="alert alert-secondary py-2 small d-none" id="permAdminNote">
        <i class="bi bi-shield-check me-1"></i>ผู้ดูแลระบบเข้าได้และแก้ไขได้ทุกหน้าเสมอ ไม่ต้องกำหนดรายหน้า
    </div>

    <div id="permTableWrap">
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="small text-muted align-self-center me-1">ตั้งทั้งหมดเป็น:</span>
            @foreach ($levelLabels as $lvl => $label)
                <button type="button" class="btn btn-outline-secondary btn-sm" data-perm-all="{{ $lvl }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>หน้า</th>
                        @foreach ($levelLabels as $label)
                            <th class="text-center" style="width: 130px;">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Support\Permission::groups() as $groupLabel => $modules)
                        <tr class="table-light">
                            <td colspan="4" class="small fw-semibold">{{ $groupLabel }}</td>
                        </tr>

                        @foreach ($modules as $key => $label)
                            @php
                                $value = $savedPerms[$key] ?? \App\Support\Permission::defaultFor($currentRole);
                            @endphp
                            <tr>
                                <td>{{ $label }}</td>
                                @foreach ($levelLabels as $lvl => $lvlLabel)
                                    <td class="text-center">
                                        <input class="form-check-input" type="radio"
                                            name="permissions[{{ $key }}]"
                                            value="{{ $lvl }}"
                                            aria-label="{{ $label }}: {{ $lvlLabel }}"
                                            data-perm-level="{{ $lvl }}"
                                            @checked($value === $lvl)>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        var box = document.getElementById('permissionBox');
        if (!box) return;

        var form       = box.closest('form');
        var roleSelect = form ? form.querySelector('[name="role"]') : null;
        var tableWrap  = document.getElementById('permTableWrap');
        var adminNote  = document.getElementById('permAdminNote');

        function editRadios() {
            return box.querySelectorAll('input[data-perm-level="edit"]');
        }

        // ปรับตารางตาม role ที่เลือก
        function applyRole() {
            var role = roleSelect ? roleSelect.value : 'staff';

            tableWrap.classList.toggle('d-none', role === 'admin');
            adminNote.classList.toggle('d-none', role !== 'admin');

            // viewer แก้ไขไม่ได้อยู่แล้ว ปิดช่อง "ดูและแก้ไข" และย้ายค่าที่เลือกไว้ลงมาเป็น "ดูอย่างเดียว"
            editRadios().forEach(function (radio) {
                var isViewer = role === 'viewer';
                radio.disabled = isViewer;

                if (isViewer && radio.checked) {
                    var viewRadio = box.querySelector(
                        'input[name="' + radio.name + '"][data-perm-level="view"]'
                    );
                    if (viewRadio) viewRadio.checked = true;
                }
            });
        }

        // ปุ่ม "ตั้งทั้งหมดเป็น..."
        box.querySelectorAll('[data-perm-all]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var level = btn.getAttribute('data-perm-all');

                box.querySelectorAll('input[data-perm-level="' + level + '"]').forEach(function (radio) {
                    if (radio.disabled) {
                        // viewer กด "ดูและแก้ไข" ทั้งหมด → ได้แค่ดู
                        var viewRadio = box.querySelector(
                            'input[name="' + radio.name + '"][data-perm-level="view"]'
                        );
                        if (viewRadio) viewRadio.checked = true;
                    } else {
                        radio.checked = true;
                    }
                });
            });
        });

        if (roleSelect) {
            roleSelect.addEventListener('change', applyRole);
        }

        applyRole();
    })();
</script>