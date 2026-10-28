{{--
    (เดิม) ปุ่มจัดการแบบไอคอน — ตอนนี้ใช้ <x-row-actions> แทนทั้งระบบแล้ว
    เก็บไฟล์นี้ไว้เผื่อมีหน้าที่ยัง @include อยู่ จะได้หน้าตาเหมือนกันทุกหน้า

    ตัวแปรที่รับ (เหมือนเดิม): $showUrl, $editUrl, $deleteUrl, $label
--}}
<x-row-actions :show="$showUrl ?? null"
               :edit="$editUrl ?? null"
               :delete="$deleteUrl ?? null"
               :label="$label ?? 'รายการนี้'" />
