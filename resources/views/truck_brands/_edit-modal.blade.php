{{--
    หน้าต่างแก้ไขยี่ห้อรถ (ใช้ร่วมทุกแถวในแท็บยี่ห้อ)
    ต้องส่ง: $brandEditData (closure จาก trucks/index)
    ปุ่มในแถว: <button data-brand-edit='@json($brandEditData($brand))'>

    เปิดอัตโนมัติเมื่อ
      - บันทึกแล้ว validate ไม่ผ่าน (error อยู่ใน bag "updateBrand")
      - เปิด URL ?tab=brands&edit={id} (เช่น ลิงก์เดิม truck_brands/{id}/edit)
--}}
@php
    $be        = $errors->updateBrand;
    $keepQuery = array_filter(request()->only('page', 'search', 'per_page'), fn ($v) => $v !== null && $v !== '');

    $editOpenId = $be->any() ? (string) old('_brand_id') : (string) request('edit');
    $editOpen   = $editOpenId !== '' ? \App\Models\TruckBrand::find($editOpenId) : null;
    $editOld    = $be->any() ? ['name_brand' => old('name_brand')] : null;
@endphp

<div class="modal fade" id="brandEditModal" tabindex="-1" aria-labelledby="brandEditTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="#" autocomplete="off" class="modal-content border-0 shadow"
              data-confirm="ต้องการบันทึกการแก้ไขยี่ห้อรถบรรทุกนี้หรือไม่" data-confirm-title="ยืนยันการแก้ไขข้อมูล"
              data-confirm-ok="บันทึกการแก้ไข" data-confirm-variant="primary">
            @csrf @method('PUT')
            <input type="hidden" name="_brand_id" data-edit-id>
            @foreach ($keepQuery as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="brandEditTitle">
                    แก้ไขยี่ห้อ <span class="text-muted fw-normal fs-6" data-edit-name></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body">
                @include('partials.modal-errors', ['bag' => $be])

                <div class="mb-3">
                    <label class="form-label" for="be_name_brand">ชื่อยี่ห้อ</label>
                    <input type="text" name="name_brand" id="be_name_brand"
                           class="form-control {{ $be->has('name_brand') ? 'is-invalid' : '' }}"
                           placeholder="เช่น ISUZU, HINO, FUSO" required maxlength="100"
                           data-required-msg="กรุณากรอกชื่อยี่ห้อ">
                </div>
                <div class="form-text mt-n2">ระบบเปลี่ยนเป็นตัวพิมพ์ใหญ่ให้อัตโนมัติ</div>
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
        const modalEl = document.getElementById('brandEditModal');
        if (!modalEl) return;
        const form  = modalEl.querySelector('form');
        const input = form.elements['name_brand'];

        function open(brand, old) {
            if (!old) ModalForm.clearErrors(modalEl);

            modalEl.querySelector('[data-edit-name]').textContent = '— ' + brand.name_brand;
            form.querySelector('[data-edit-id]').value = brand.id;
            form.action = brand.url;
            ModalForm.fill(form, Object.assign({ name_brand: brand.name_brand }, old || {}));
            ModalForm.open(modalEl);
        }

        modalEl.addEventListener('shown.bs.modal', () => { input.focus(); input.select(); });

        document.querySelectorAll('[data-brand-edit]').forEach((btn) =>
            btn.addEventListener('click', () => open(JSON.parse(btn.dataset.brandEdit))));

        @if ($editOpen)
            open(@json($brandEditData($editOpen)), @json($editOld));

            const url = new URL(window.location.href);
            if (url.searchParams.has('edit')) {
                url.searchParams.delete('edit');
                history.replaceState(null, '', url);
            }
        @endif
    });
</script>
