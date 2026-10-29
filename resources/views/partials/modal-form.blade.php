{{--
    ตัวช่วยหน้าต่างเพิ่ม / แก้ไข (modal) ใช้ร่วมทุกหน้า

        ModalForm.fill(form, data)       เติมค่าให้ทุกช่องที่ชื่อตรงกับ key ของ data
                                         (ข้ามช่องไฟล์ · checkbox/radio ติ๊กตามค่า · select ที่ไม่มีค่านั้นจะเพิ่มตัวเลือกให้)
        ModalForm.clearErrors(modalEl)   ล้างกรอบแดง + กล่องข้อผิดพลาดจากการเปิดครั้งก่อน
        ModalForm.open(modalEl)          เปิดหน้าต่าง
--}}
@once
    <style>
        .modal-section {
            font-size: .8rem; font-weight: 600; letter-spacing: .03em;
            color: var(--bs-secondary-color);
            border-bottom: 1px solid var(--bs-border-color);
            padding-bottom: .4rem; margin-bottom: .9rem;
        }
    </style>
    <script>
        window.ModalForm = (function () {
            function setField(el, value) {
                if (el.type === 'file') return;
                if (el.type === 'checkbox') { el.checked = !!value && value !== '0'; return; }
                if (el.type === 'radio')    { el.checked = String(el.value) === String(value ?? ''); return; }
                const v = value === null || value === undefined ? '' : String(value);
                if (el.tagName === 'SELECT' && v !== '' && ![...el.options].some((o) => o.value === v)) {
                    el.add(new Option(v, v));
                }
                el.value = v;
            }

            function fill(form, data) {
                Object.keys(data || {}).forEach((name) => {
                    const field = form.elements[name];
                    if (!field) return;
                    if (field instanceof RadioNodeList) field.forEach((el) => setField(el, data[name]));
                    else setField(field, data[name]);
                });
            }

            function clearErrors(modalEl) {
                modalEl.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
                modalEl.querySelectorAll('[data-modal-errors], .invalid-feedback').forEach((el) => el.remove());
            }

            function open(modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            return { fill, clearErrors, open };
        })();
    </script>
@endonce
