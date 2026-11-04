document.addEventListener('DOMContentLoaded', function () {

    const modalEl = document.getElementById('confirmModal');
    if (!modalEl) return;

    const modal    = new bootstrap.Modal(modalEl);
    const titleEl  = document.getElementById('confirmModalTitle');
    const bodyEl   = document.getElementById('confirmModalBody');
    const okBtn    = document.getElementById('confirmModalOk');
    const reasonWrap = document.getElementById('confirmModalReasonWrap');
    const reasonEl   = document.getElementById('confirmModalReason');

    let pendingForm = null;
    let pendingSubmitter = null;
    let parentModal = null;
    let confirmedNow = false;

    const variantClass = {
        danger:  'btn-danger',
        warning: 'btn-warning',
        success: 'btn-success',
        primary: 'btn-primary',
    };

    document.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-confirm]');
        if (!form) return;

        if (e.defaultPrevented) return;

        if (form.dataset.confirmed === 'true') return;

        e.preventDefault();
        pendingForm = form;
        pendingSubmitter = e.submitter || null;

        titleEl.textContent = form.dataset.confirmTitle || 'ยืนยันการทำรายการ';
        bodyEl.textContent  = form.dataset.confirm      || 'คุณต้องการดำเนินการต่อหรือไม่';
        okBtn.textContent   = form.dataset.confirmOk    || 'ยืนยัน';

        okBtn.className = 'btn ' + (variantClass[form.dataset.confirmVariant] || 'btn-dark');

        if (reasonWrap) {
            const needReason = !!form.dataset.confirmReason;
            reasonWrap.classList.toggle('d-none', !needReason);
            reasonEl.value = '';
            reasonEl.classList.remove('is-invalid');
        }

        const parentEl = form.closest('.modal');
        if (parentEl && parentEl !== modalEl && parentEl.classList.contains('show')) {
            parentModal = bootstrap.Modal.getOrCreateInstance(parentEl);
            parentEl.addEventListener('hidden.bs.modal', function () { modal.show(); }, { once: true });
            parentModal.hide();
        } else {
            parentModal = null;
            modal.show();
        }
    });

    okBtn.addEventListener('click', function () {
        if (!pendingForm) return;

        const form = pendingForm;

        if (reasonWrap && form.dataset.confirmReason) {
            const reason = reasonEl.value.trim();
            if (reason.length < 3) {
                reasonEl.classList.add('is-invalid');
                reasonEl.focus();
                return;
            }
            let hidden = form.querySelector('input[name="' + form.dataset.confirmReason + '"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = form.dataset.confirmReason;
                form.appendChild(hidden);
            }
            hidden.value = reason;
        }

        if (pendingSubmitter && pendingSubmitter.name) {
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = pendingSubmitter.name;
            hidden.value = pendingSubmitter.value;
            form.appendChild(hidden);
        }

        form.dataset.confirmed = 'true';
        confirmedNow = true;
        modal.hide();

        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (b) {
            b.disabled = true;
        });

        form.submit();
        pendingForm = null;
        pendingSubmitter = null;
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (!confirmedNow && parentModal) parentModal.show();
        parentModal = null;
        confirmedNow = false;
        pendingForm = null;
        pendingSubmitter = null;
    });

    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            delete form.dataset.confirmed;
            form.querySelectorAll('button[disabled]').forEach(function (b) { b.disabled = false; });
        });
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        if (reasonWrap && !reasonWrap.classList.contains('d-none')) {
            reasonEl.focus();
        } else {
            okBtn.focus();
        }
    });

});
