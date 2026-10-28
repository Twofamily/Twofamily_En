{{--
    เมนู "ดู" ของรุ่นรถใน <x-row-actions> — เปิดหน้าต่าง #modelDetailModal
    ตัวแปร: $m (TruckModel ที่โหลด brand + trucks แล้ว), $canEdit
--}}
@php
    $detail = [
        'title'  => trim(($m->brand->name_brand ?? '') . ' ' . $m->name_model)
                    . ($m->model_year ? " ({$m->model_year})" : ''),
        'active' => (bool) $m->is_active,
        'specs'  => $m->specList(),
        'trucks' => $m->trucks->map(fn ($t) => [
            'id'     => $t->id_truck,
            'status' => $t->status_label,
            'color'  => $t->status_color,
            'url'    => route('trucks.show', $t->id_truck),
        ])->values(),
        'edit'   => $canEdit ? route('truck_models.edit', $m) : null,
    ];
@endphp

<x-row-actions.link href="#" icon="bi-eye"
                    data-bs-toggle="modal" data-bs-target="#modelDetailModal"
                    :data-model="json_encode($detail, JSON_UNESCAPED_UNICODE)">
    ดู
</x-row-actions.link>
