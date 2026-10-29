{{--
    หน้าต่างเพิ่มลูกค้าใหม่ "ซ้อน" บนหน้าต่างเอกสาร (ใบเสนอราคา / ใบสั่งขาย) — ไม่ต้องย้ายไปหน้าลูกค้า

        @include('customers._quick-modal', ['action' => route('customers.store.ajax')])

    วางไว้ "นอก" <form> ของเอกสาร (ฟอร์มซ้อนฟอร์มไม่ได้)
    ปุ่มเปิด: <button type="button" data-quick-customer> ที่อยู่ในฟอร์มเดียวกับ <select data-customer-select>

    ลำดับการทำงาน
        1. กดปุ่ม → ซ่อนหน้าต่างเอกสารไว้ก่อน (ค่าที่กรอกยังอยู่ครบ) แล้วเปิดหน้าต่างนี้
           (Bootstrap ไม่รองรับ modal ซ้อนกันสองชั้น — focus จะแย่งกัน เลยใช้วิธีสลับแทน)
        2. บันทึกผ่าน fetch (JSON) → ได้ลูกค้าใหม่กลับมา → เพิ่มลงรายการลูกค้าและเลือกให้เลย
        3. ปิดหน้าต่างนี้ (บันทึกหรือยกเลิก) → กลับไปหน้าต่างเอกสารเดิม
    ใช้ช่องกรอกชุดเดียวกับหน้าลูกค้า (customers._fields) และตรวจข้อมูลด้วยกฎเดียวกัน (validateForm)
--}}
@once
<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-labelledby="quickCustomerTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" data-quick-customer-form data-action="{{ $action }}">
            <div class="modal-header">
                <h5 class="modal-title" id="quickCustomerTitle">
                    <i class="bi bi-person-plus me-1"></i>เพิ่มลูกค้าใหม่
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger py-2 small" data-qc-error hidden></div>

                @include('customers._fields', ['p' => 'qc_', 'v' => [], 'bag' => new \Illuminate\Support\MessageBag()])
            </div>

            <div class="modal-footer">
                <span class="small text-muted me-auto"><span class="req-star">*</span> = จำเป็นต้องกรอก</span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i>กลับ
                </button>
                <button type="submit" class="btn btn-dark" data-qc-save>
                    <i class="bi bi-check-lg me-1"></i>บันทึกลูกค้า
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    #quickCustomerModal .customer-section {
        font-size: .8rem; font-weight: 600; letter-spacing: .03em;
        color: var(--bs-secondary-color);
        border-bottom: 1px solid var(--bs-border-color);
        padding-bottom: .4rem; margin-bottom: .9rem;
    }
</style>

@include('partials.thai-address')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('quickCustomerModal');
        const modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
        const form    = modalEl.querySelector('form');
        const errBox  = form.querySelector('[data-qc-error]');
        const saveBtn = form.querySelector('[data-qc-save]');

        let parentModalEl = null;   // หน้าต่างเอกสารที่ซ่อนไว้
        let targetSelect  = null;   // <select> ลูกค้าที่จะเติมลูกค้าใหม่ให้

        /* ---------- บุคคล / บริษัท: แสดงเฉพาะช่องที่เกี่ยวข้อง (ช่องที่ซ่อน = disabled ไม่ถูกส่ง) ---------- */
        function applyBranch() {
            const kind = form.querySelector('[data-branch-kind]');
            const box  = form.querySelector('[data-branch-no-box]');
            const show = !kind.disabled && kind.value === 'branch';
            box.classList.toggle('d-none', !show);
            box.querySelector('input').disabled = !show;
        }

        function applyType() {
            const type = form.querySelector('[data-customer-type]:checked')?.value || 'person';
            form.querySelectorAll('[data-type-block]').forEach((block) => {
                const active = block.dataset.typeBlock === type;
                block.classList.toggle('d-none', !active);
                block.querySelectorAll('input, select, textarea').forEach((el) => el.disabled = !active);
            });
            applyBranch();
        }

        form.querySelectorAll('[data-customer-type]').forEach((r) => r.addEventListener('change', applyType));
        form.querySelector('[data-branch-kind]').addEventListener('change', applyBranch);
        applyType();

        const setAddress = ThaiAddress.setup(form);

        /* ---------- ข้อความผิดพลาด ---------- */
        function showErrors(messages) {
            errBox.innerHTML = '';
            if (!messages.length) { errBox.hidden = true; return; }
            const ul = document.createElement('ul');
            ul.className = 'mb-0';
            messages.forEach((m) => { const li = document.createElement('li'); li.textContent = m; ul.appendChild(li); });
            errBox.appendChild(ul);
            errBox.hidden = false;
            errBox.scrollIntoView({ block: 'nearest' });
        }

        function clearErrors() {
            showErrors([]);
            delete form.dataset.fvSubmitted;
            form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
            form.querySelectorAll('.fv-msg').forEach((el) => el.classList.remove('d-block'));
        }

        function resetForm() {
            form.reset();
            clearErrors();
            applyType();
            setAddress({});
        }

        /* ---------- เปิดจากปุ่ม "ลูกค้าใหม่" ในหน้าต่างเอกสาร ---------- */
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-quick-customer]');
            if (!btn) return;

            targetSelect  = btn.closest('form')?.querySelector('[data-customer-select]') || null;
            parentModalEl = btn.closest('.modal');
            resetForm();

            if (parentModalEl) {
                // รอให้หน้าต่างเอกสารปิดสนิทก่อน แล้วค่อยเปิดหน้าต่างลูกค้า
                parentModalEl.addEventListener('hidden.bs.modal', () => modal.show(), { once: true });
                bootstrap.Modal.getOrCreateInstance(parentModalEl).hide();
            } else {
                modal.show();
            }
        });

        modalEl.addEventListener('shown.bs.modal', () => {
            form.querySelector('[data-type-block]:not(.d-none) input:not([disabled])')?.focus();
        });

        // ปิดหน้าต่างนี้ (บันทึกแล้ว / กดกลับ) → เปิดหน้าต่างเอกสารเดิมคืน
        modalEl.addEventListener('hidden.bs.modal', () => {
            if (parentModalEl) bootstrap.Modal.getOrCreateInstance(parentModalEl).show();
            parentModalEl = null;
        });

        /* ---------- บันทึก ---------- */
        // ช่องว่าง / รูปแบบผิด → form-validation.js ดักไว้ก่อนแล้ว (กรอบแดง + ข้อความใต้ช่อง) จะมาถึงตรงนี้เมื่อผ่านเท่านั้น
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            showErrors([]);

            saveBtn.disabled = true;
            try {
                const res = await fetch(form.dataset.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: new FormData(form),   // ช่องที่ disabled (ประเภทที่ไม่ได้เลือก) จะไม่ถูกส่ง
                });

                if (res.status === 422) {
                    const err = await res.json();
                    Object.keys(err.errors || {}).forEach((name) => form.elements[name]?.classList?.add('is-invalid'));
                    showErrors(Object.values(err.errors || {}).flat());
                    return;
                }
                if (res.status === 419) { showErrors(['เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่']); return; }
                if (res.status === 403) { showErrors(['บัญชีนี้ไม่มีสิทธิ์เพิ่มลูกค้า']); return; }
                if (!res.ok)            { showErrors(['บันทึกไม่สำเร็จ (รหัส ' + res.status + ')']); return; }

                const customer = await res.json();

                // เติมลูกค้าใหม่ลงรายการ แล้วเลือกให้เลย
                if (targetSelect) {
                    targetSelect.add(new Option(customer.name_customer, customer.id_customer, true, true));
                    targetSelect.classList.remove('is-invalid');
                    targetSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                modal.hide();   // → กลับไปหน้าต่างเอกสาร
            } catch (err) {
                showErrors(['เชื่อมต่อเซิร์ฟเวอร์ไม่ได้']);
            } finally {
                saveBtn.disabled = false;
            }
        });
    });
</script>
@endonce
