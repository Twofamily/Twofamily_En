document.addEventListener('DOMContentLoaded', function () {

    const modalEl = document.getElementById('confirmModal');
    if (!modalEl) return;

    const modal    = new bootstrap.Modal(modalEl);
    const titleEl  = document.getElementById('confirmModalTitle');
    const bodyEl   = document.getElementById('confirmModalBody');
    const okBtn    = document.getElementById('confirmModalOk');

    let pendingForm = null;

    const variantClass = {
        danger:  'btn-danger',
        warning: 'btn-warning',
        success: 'btn-success',
        primary: 'btn-primary',
    };

    // ดักการ submit ของทุกฟอร์มที่มี data-confirm
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-confirm]');
        if (!form) return;

        // ถ้าผ่านการยืนยันแล้ว ให้ปล่อยผ่าน
        if (form.dataset.confirmed === 'true') return;

        e.preventDefault();
        pendingForm = form;

        titleEl.textContent = form.dataset.confirmTitle || 'ยืนยันการทำรายการ';
        bodyEl.textContent  = form.dataset.confirm      || 'คุณต้องการดำเนินการต่อหรือไม่';
        okBtn.textContent   = form.dataset.confirmOk    || 'ยืนยัน';

        okBtn.className = 'btn ' + (variantClass[form.dataset.confirmVariant] || 'btn-dark');

        modal.show();
    });

    okBtn.addEventListener('click', function () {
        if (!pendingForm) return;

        pendingForm.dataset.confirmed = 'true';
        modal.hide();
        pendingForm.submit();
        pendingForm = null;
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        pendingForm = null;
    });

});