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
