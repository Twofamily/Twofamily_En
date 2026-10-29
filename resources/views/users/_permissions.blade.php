{{--
    ตารางกำหนดสิทธิ์รายหน้า — วางไว้ "ข้างใน <form>" เดียวกับช่องเลือก role
    ใช้ได้หลายที่ในหน้าเดียว (หน้าต่างเพิ่ม + หน้าต่างแก้ไข) เพราะไม่ใช้ id

    @include('users._permissions', [
        'savedPerms'  => [...],     // ค่าที่เลือกไว้ [module => none|view|edit]
        'currentRole' => 'staff',
    ])

    สคริปต์ตั้งค่าจากภายนอกได้ (หน้าต่างแก้ไขเติมค่าของผู้ใช้แต่ละคน):
        window.PermissionBox.set(boxElement, {camps: 'edit', ...}, 'staff')
--}}
@php
    $savedPerms  = $savedPerms ?? [];
    $currentRole = $currentRole ?? \App\Models\User::ROLE_STAFF;
    $levelLabels = \App\Support\Permission::LEVEL_LABELS;
@endphp

<div class="mb-1" data-perm-box>
    <label class="form-label fw-semibold">สิทธิ์การเข้าใช้งานแต่ละหน้า</label>
    <div class="form-text text-muted mt-0 mb-2">
        หน้าที่ตั้งเป็น "ไม่มีสิทธิ์" จะถูกซ่อนจากเมนูและเปิดผ่านลิงก์ตรงไม่ได้
        ผู้ดูข้อมูล (viewer) เลือกได้สูงสุดแค่ "ดูอย่างเดียว"
    </div>

    <div class="alert alert-secondary py-2 small d-none" data-perm-admin-note>
        <i class="bi bi-shield-check me-1"></i>ผู้ดูแลระบบเข้าได้และแก้ไขได้ทุกหน้าเสมอ ไม่ต้องกำหนดรายหน้า
    </div>

    <div data-perm-table>
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="small text-muted align-self-center me-1">ตั้งทั้งหมดเป็น:</span>
            @foreach ($levelLabels as $lvl => $label)
                <button type="button" class="btn btn-outline-secondary btn-sm" data-perm-all="{{ $lvl }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="table-responsive border rounded" style="max-height: 320px;">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th>หน้า</th>
                        @foreach ($levelLabels as $label)
                            <th class="text-center" style="width: 120px;">{{ $label }}</th>
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
                                            data-perm-module="{{ $key }}"
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

@once
    <script>
        window.PermissionBox = (function () {
            // ค่าเริ่มต้นตาม role เมื่อหน้านั้นยังไม่เคยตั้ง (ตรงกับ Permission::defaultFor)
            var DEFAULTS = { admin: 'edit', staff: 'edit', viewer: 'view' };

            function radio(box, module, level) {
                return box.querySelector('input[data-perm-module="' + module + '"][data-perm-level="' + level + '"]');
            }

            function modules(box) {
                var seen = {};
                box.querySelectorAll('input[data-perm-module]').forEach(function (r) { seen[r.dataset.permModule] = true; });
                return Object.keys(seen);
            }

            function roleOf(box) {
                var form = box.closest('form');
                var select = form ? form.querySelector('[name="role"]') : null;
                return select ? select.value : 'staff';
            }

            // ปรับตารางตาม role: admin ซ่อนตาราง · viewer ปิดช่อง "ดูและแก้ไข"
            function applyRole(box) {
                var role = roleOf(box);
                box.querySelector('[data-perm-table]').classList.toggle('d-none', role === 'admin');
                box.querySelector('[data-perm-admin-note]').classList.toggle('d-none', role !== 'admin');

                box.querySelectorAll('input[data-perm-level="edit"]').forEach(function (r) {
                    r.disabled = role === 'viewer';
                    if (role === 'viewer' && r.checked) {
                        var view = radio(box, r.dataset.permModule, 'view');
                        if (view) view.checked = true;
                    }
                });
            }

            // ตั้งค่าทั้งตาราง (ใช้ตอนเปิดหน้าต่างแก้ไขของผู้ใช้แต่ละคน)
            function set(box, perms, role) {
                perms = perms || {};
                modules(box).forEach(function (m) {
                    var level = perms[m] || DEFAULTS[role] || 'edit';
                    var r = radio(box, m, level);
                    if (r) r.checked = true;
                });
                applyRole(box);
            }

            function init(box) {
                if (box.dataset.permReady) return;
                box.dataset.permReady = '1';

                box.querySelectorAll('[data-perm-all]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var level = btn.getAttribute('data-perm-all');
                        modules(box).forEach(function (m) {
                            var r = radio(box, m, level);
                            if (r && r.disabled) r = radio(box, m, 'view');
                            if (r) r.checked = true;
                        });
                    });
                });

                var form = box.closest('form');
                var select = form ? form.querySelector('[name="role"]') : null;
                if (select) select.addEventListener('change', function () { applyRole(box); });

                applyRole(box);
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-perm-box]').forEach(init);
            });

            return { set: set, applyRole: applyRole };
        })();
    </script>
@endonce
