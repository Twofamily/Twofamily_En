/*
 * confirm.js — modal ยืนยันก่อนส่งฟอร์ม ใช้ร่วมกันทั้งระบบ
 *
 * ใส่ data-confirm ที่ <form> (ไม่ใช่ที่ <button>)
 *   data-confirm="ข้อความในกล่อง"
 *   data-confirm-title="หัวข้อ"            (ไม่ใส่ = ยืนยันการทำรายการ)
 *   data-confirm-ok="ข้อความปุ่มยืนยัน"    (ไม่ใส่ = ยืนยัน)
 *   data-confirm-variant="danger|warning|success|primary"
 *
 * ⚠ ต้องโหลด "หลัง" form-validation.js — ฟอร์มที่กรอกไม่ครบจะไม่เด้ง modal
 */
document.addEventListener('DOMContentLoaded', function () {

    const modalEl = document.getElementById('confirmModal');
    if (!modalEl) return;

    const modal    = new bootstrap.Modal(modalEl);
    const titleEl  = document.getElementById('confirmModalTitle');
    const bodyEl   = document.getElementById('confirmModalBody');
    const okBtn    = document.getElementById('confirmModalOk');

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

    // ดักการ submit ของทุกฟอร์มที่มี data-confirm
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-confirm]');
        if (!form) return;

        // สคริปต์ของหน้านั้นยกเลิกการส่งไปแล้ว (เช่น ตรวจข้อมูลไม่ผ่าน) → ไม่ต้องถาม
        if (e.defaultPrevented) return;

        // ถ้าผ่านการยืนยันแล้ว ให้ปล่อยผ่าน
        if (form.dataset.confirmed === 'true') return;

        e.preventDefault();
        pendingForm = form;
        pendingSubmitter = e.submitter || null;

        titleEl.textContent = form.dataset.confirmTitle || 'ยืนยันการทำรายการ';
        bodyEl.textContent  = form.dataset.confirm      || 'คุณต้องการดำเนินการต่อหรือไม่';
        okBtn.textContent   = form.dataset.confirmOk    || 'ยืนยัน';

        okBtn.className = 'btn ' + (variantClass[form.dataset.confirmVariant] || 'btn-dark');

        // ฟอร์มอยู่ใน modal อีกอัน (เช่น เปลี่ยนสถานะรถ) → ปิดอันนั้นก่อน
        // ถ้ากดยกเลิก จะเปิดกลับมาให้ (Bootstrap ซ้อน modal สองชั้นแล้วโฟกัสตีกัน)
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

        // form.submit() ไม่ส่งค่าของปุ่มที่กด → เติมเป็น hidden ให้เอง
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

        // กันกดซ้ำระหว่างรอหน้าโหลด
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (b) {
            b.disabled = true;
        });

        form.submit();
        pendingForm = null;
        pendingSubmitter = null;
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        // กดยกเลิก → เปิด modal เดิมของฟอร์มกลับมา
        if (!confirmedNow && parentModal) parentModal.show();
        parentModal = null;
        confirmedNow = false;
        pendingForm = null;
        pendingSubmitter = null;
    });

    // กดย้อนกลับมาหน้านี้ (browser จำหน้าเดิมไว้) → คืนสถานะฟอร์มให้กดใหม่ได้
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            delete form.dataset.confirmed;
            form.querySelectorAll('button[disabled]').forEach(function (b) { b.disabled = false; });
        });
    });

    // เปิดกล่องแล้วโฟกัสปุ่มยืนยัน กด Enter ได้เลย
    modalEl.addEventListener('shown.bs.modal', function () {
        okBtn.focus();
    });

});
