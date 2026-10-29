/*
 * date-input.js — ช่องวันที่ทั้งระบบแสดงเป็น วัน/เดือน/ปี (dd/mm/yyyy)
 *
 * ปัญหาเดิม: <input type="date"> ของ browser แสดงรูปแบบตามภาษาเครื่อง
 * (Chrome ภาษาอังกฤษจะเป็น mm/dd/yyyy) ไม่ตรงกับตารางที่แสดง dd/mm/yyyy
 *
 * วิธีแก้: ครอบทุก input[type=date] ด้วย flatpickr (ไฟล์อยู่ใน public/js/vendor/flatpickr ใช้งาน offline ได้)
 *  - ช่องที่ผู้ใช้เห็น  : 24/10/2026
 *  - ค่าที่ส่งไป server : 2026-10-24 (เหมือนเดิม controller ไม่ต้องแก้)
 *  - อ่าน min / max / required / onchange จาก input เดิมให้อัตโนมัติ
 *
 * ถ้าโหลด flatpickr ไม่ได้ จะใช้ช่องวันที่ปกติของ browser แทน (ระบบยังใช้งานได้)
 *
 * ⚠ ต้องโหลด "ก่อน" form-validation.js เพื่อให้ดอกจันและกรอบแดงไปติดที่ช่องที่ผู้ใช้เห็น
 */
(function () {
    'use strict';

    function init(el) {
        if (el.__fp || !window.flatpickr) return;

        var inModal = !!el.closest('.modal');

        var fp = window.flatpickr(el, {
            locale: (window.flatpickr.l10ns && window.flatpickr.l10ns.th) || 'default',
            dateFormat: 'Y-m-d',      // ค่าที่ส่งไป server
            altInput: true,
            altFormat: 'd/m/Y',       // ค่าที่แสดงให้ผู้ใช้เห็น
            altInputClass: el.className,
            allowInput: true,
            disableMobile: true,      // มือถือก็แสดง dd/mm/yyyy เหมือนกัน
            minDate: el.min || null,
            maxDate: el.max || null,
            static: inModal,          // ใน modal ให้ปฏิทินอยู่ใน modal (ไม่โดน focus trap)
            parseDate: function (str, format) {
                // พิมพ์เองได้ทั้ง 24/10/2026 และ 2026-10-24
                var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(str.trim());
                if (m) return new Date(+m[3], +m[2] - 1, +m[1]);
                return window.flatpickr.parseDate(str, format);
            },
        });

        el.__fp = fp;

        var alt = fp.altInput;
        if (!alt) return;

        // ส่งต่อข้อมูลที่ form-validation.js ใช้ ไปให้ช่องที่ผู้ใช้เห็น
        alt.placeholder = el.placeholder || 'วว/ดด/ปปปป';
        if (el.dataset.requiredMsg) alt.dataset.requiredMsg = el.dataset.requiredMsg;
        if (el.getAttribute('aria-label')) alt.setAttribute('aria-label', el.getAttribute('aria-label'));
        if (el.id) {
            // label for="id" เดิม → ชี้มาที่ช่องที่มองเห็น
            var label = document.querySelector('label[for="' + el.id + '"]');
            if (label) {
                alt.id = el.id + '_display';
                label.htmlFor = alt.id;
            }
        }
    }

    function initAll(root) {
        (root || document).querySelectorAll('input[type="date"]').forEach(init);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAll();

        // ช่องวันที่ที่ถูกเพิ่มเข้ามาทีหลังด้วย JS
        var pending = false;
        new MutationObserver(function () {
            if (pending) return;
            pending = true;
            setTimeout(function () {
                pending = false;
                initAll();
            }, 50);
        }).observe(document.body, { childList: true, subtree: true });
    });

    window.initDateInputs = initAll;
})();
