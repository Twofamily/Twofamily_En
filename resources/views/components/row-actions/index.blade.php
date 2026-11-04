@props([
    'show'           => null,
    'edit'           => null,
    'delete'         => null,
    'label'          => 'รายการนี้',
    'deleteLabel'    => 'ลบ',
    'deleteConfirm'  => null,
    'deleteTitle'    => 'ยืนยันการลบข้อมูล',
    'deleteOk'       => 'ลบข้อมูล',
    'deleteDisabled' => null,
    'deleteIcon'     => 'bi-trash',
    'deleteReason'   => null,   // ชื่อฟิลด์เหตุผล เช่น 'cancel_reason' → กล่องยืนยันมีช่องให้กรอกเหตุผล
])

@php
    $hasExtra  = $slot->hasActualContent();
    $hasDelete = $delete || $deleteDisabled;
@endphp

@if ($show || $edit || $hasExtra || $hasDelete)
    <div class="dropdown row-actions">
        <button type="button" class="btn row-actions-toggle"
                data-bs-toggle="dropdown"
                data-bs-popper-config='{"strategy":"fixed"}'
                aria-expanded="false"
                aria-label="ตัวเลือกของ {{ $label }}"
                title="ตัวเลือก">
            <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end">
            @if ($show)
                <li>
                    <a class="dropdown-item" href="{{ $show }}">
                        <i class="bi bi-eye" aria-hidden="true"></i>ดู
                    </a>
                </li>
            @endif

            @if ($edit)
                <li>
                    <a class="dropdown-item" href="{{ $edit }}">
                        <i class="bi bi-pencil-square" aria-hidden="true"></i>แก้ไข
                    </a>
                </li>
            @endif

            {{ $slot }}

            @if ($hasDelete)
                @if ($show || $edit || $hasExtra)
                    <li><hr class="dropdown-divider"></li>
                @endif

                <li>
                    @if ($delete)
                        <form method="POST" action="{{ $delete }}" class="m-0"
                              data-confirm="{{ $deleteConfirm ?? "ข้อมูล {$label} จะถูกลบออกจากระบบ" }}"
                              data-confirm-title="{{ $deleteTitle }}"
                              data-confirm-variant="danger"
                              data-confirm-ok="{{ $deleteOk }}"
                              @if ($deleteReason) data-confirm-reason="{{ $deleteReason }}" @endif>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi {{ $deleteIcon }}" aria-hidden="true"></i>{{ $deleteLabel }}
                            </button>
                        </form>
                    @else
                        <span class="dropdown-item disabled row-actions-disabled" aria-disabled="true">
                            <i class="bi {{ $deleteIcon }}" aria-hidden="true"></i>
                            <span>
                                {{ $deleteLabel }}
                                <small class="d-block">{{ $deleteDisabled }}</small>
                            </span>
                        </span>
                    @endif
                </li>
            @endif
        </ul>
    </div>
@endif
