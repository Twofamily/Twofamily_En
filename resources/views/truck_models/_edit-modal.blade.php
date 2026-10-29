{{--
    หน้าต่างแก้ไขรุ่นรถ (ใช้ร่วมทุกแถวในแท็บรุ่นรถ)
    ต้องส่ง: $brandOptions (ยี่ห้อ), $modelEditData (closure จาก trucks/index)
    ปุ่มในแถว: <button data-model-edit='@json($modelEditData($m))'>

    เปิดอัตโนมัติเมื่อ
      - บันทึกแล้ว validate ไม่ผ่าน (error อยู่ใน bag "updateModel")
      - เปิด URL ?tab=models&edit={id} (เช่น ลิงก์เดิม truck_models/{id}/edit)
--}}
@php
    $me        = $errors->updateModel;
    $keepQuery = array_filter(request()->only('page', 'search', 'wheels', 'per_page'), fn ($v) => $v !== null && $v !== '');

    $editOpenId = $me->any() ? (string) old('_model_id') : (string) request('edit');
    $editOpen   = $editOpenId !== '' ? \App\Models\TruckModel::with('brand')->find($editOpenId) : null;
    $editOld    = $me->any() ? \Illuminate\Support\Arr::except(old(), ['_token', '_method', '_model_id']) : null;
@endphp

<div class="modal fade" id="modelEditModal" tabindex="-1" aria-labelledby="modelEditTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="#" autocomplete="off" class="modal-content border-0 shadow"
              data-confirm="ต้องการบันทึกการแก้ไขรุ่นรถบรรทุกนี้หรือไม่" data-confirm-title="ยืนยันการแก้ไขข้อมูล"
              data-confirm-ok="บันทึกการแก้ไข" data-confirm-variant="primary">
            @csrf @method('PUT')
            <input type="hidden" name="_model_id" data-edit-id>
            @foreach ($keepQuery as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modelEditTitle">
                    แก้ไขรุ่นรถ <span class="text-muted fw-normal fs-6" data-edit-name></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body">
                @include('partials.modal-errors', ['bag' => $me])

                {{-- ฟอร์มเดียวกับ modal เพิ่ม ค่าในช่องเติมด้วยสคริปต์ตอนเปิด --}}
                @include('truck_models._form', [
                    'model'  => new \App\Models\TruckModel(),
                    'brands' => $brandOptions,
                ])
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

@include('partials.modal-form')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modelEditModal');
        if (!modalEl) return;
        const form = modalEl.querySelector('form');

        // ช่องที่ validate ไม่ผ่านรอบก่อน → ใส่กรอบแดง
        const badFields = @json($me->keys());

        function open(model, old) {
            if (!old) ModalForm.clearErrors(modalEl);

            modalEl.querySelector('[data-edit-name]').textContent = '— ' + model.title;
            form.querySelector('[data-edit-id]').value = model.id;
            form.action = model.url;

            const values = Object.assign({}, model.fields, old || {});
            // controller แปลง is_active เป็น true/false ก่อน validate → ค่าที่เด้งกลับต้องแปลงคืนเป็น 1/0 ให้ตรงกับ select
            if (typeof values.is_active === 'boolean') values.is_active = values.is_active ? 1 : 0;
            ModalForm.fill(form, values);

            if (old) {
                form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
                badFields.forEach((name) => form.elements[name]?.classList.add('is-invalid'));
            }

            ModalForm.open(modalEl);
        }

        // ให้ปุ่ม "แก้ไขรุ่นนี้" ในหน้าต่าง "ดู" เรียกใช้ได้
        window.openModelEdit = open;

        document.querySelectorAll('[data-model-edit]').forEach((btn) =>
            btn.addEventListener('click', () => open(JSON.parse(btn.dataset.modelEdit))));

        @if ($editOpen)
            open(@json($modelEditData($editOpen)), @json($editOld));

            // ลบ ?edit= ออกจาก URL กดรีเฟรชแล้วจะได้ไม่เด้งซ้ำ
            const url = new URL(window.location.href);
            if (url.searchParams.has('edit')) {
                url.searchParams.delete('edit');
                history.replaceState(null, '', url);
            }
        @endif
    });
</script>
