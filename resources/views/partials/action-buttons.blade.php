{{--
    ปุ่มจัดการแบบไอคอน (ดู / แก้ไข / ลบ)

    ตัวแปรที่รับ:
    - $showUrl    (ไม่บังคับ) URL หน้าแสดงรายละเอียด ถ้าไม่ส่งมาจะไม่แสดงปุ่ม
    - $editUrl    (ไม่บังคับ) URL หน้าแก้ไข ถ้าไม่ส่งมาจะไม่แสดงปุ่ม
    - $deleteUrl  (ไม่บังคับ) URL สำหรับลบ ถ้าไม่ส่งมาจะไม่แสดงปุ่ม
    - $label      ข้อความที่ใช้อ้างถึงรายการนี้ เช่น รหัสหรือชื่อ

    ตัวอย่างการเรียกใช้:
    @include('partials.action-buttons', [
        'showUrl'   => route('camps.show', $camp->id_camp),
        'editUrl'   => route('camps.edit', $camp->id_camp),
        'deleteUrl' => route('camps.destroy', $camp->id_camp),
        'label'     => $camp->code_camp,
    ])
--}}

@php
    $showUrl = $showUrl ?? null;
    $editUrl = $editUrl ?? null;
    $deleteUrl = $deleteUrl ?? null;
    $label = $label ?? 'รายการนี้';
@endphp

<div class="action-buttons">
    {{-- ดูข้อมูล --}}
    @if ($showUrl)
        <a
            href="{{ $showUrl }}"
            class="btn action-button action-view"
            title="ดูข้อมูล"
            aria-label="ดูข้อมูล {{ $label }}"
        >
            <i class="bi bi-eye" aria-hidden="true"></i>
        </a>
    @endif

    {{-- แก้ไขข้อมูล --}}
    @if ($editUrl)
        <a
            href="{{ $editUrl }}"
            class="btn btn-outline-primary action-button"
            title="แก้ไขข้อมูล"
            aria-label="แก้ไขข้อมูล {{ $label }}"
        >
            <i class="bi bi-pencil-square" aria-hidden="true"></i>
        </a>
    @endif

    {{-- ลบข้อมูล --}}
    @if ($deleteUrl)
        <form
            method="POST"
            action="{{ $deleteUrl }}"
            class="delete-form"
            data-confirm="ข้อมูล {{ $label }} จะถูกลบออกจากระบบ"
            data-confirm-title="ยืนยันการลบข้อมูล"
            data-confirm-variant="danger"
            data-confirm-ok="ลบข้อมูล"
        >
            @csrf
            @method('DELETE')

            <button
                class="btn btn-outline-danger action-button"
                type="submit"
                title="ลบข้อมูล"
                aria-label="ลบข้อมูล {{ $label }}"
            >
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </form>
    @endif
</div>