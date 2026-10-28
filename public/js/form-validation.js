/*
 * form-validation.js — ตรวจฟอร์มก่อนบันทึก ใช้ร่วมกันทั้งระบบ
 *
 * - ฟิลด์ที่มี attribute `required` จะได้ดอกจัน * สีแดงต่อท้าย label อัตโนมัติ
 * - กดบันทึกแล้วยังมีช่องที่ไม่ผ่าน → กรอบแดง + ข้อความภาษาไทยใต้ช่อง
 *   แล้วเลื่อนจอไปที่ช่องแรกที่ผิด (ฟอร์มยังไม่ถูกส่ง และ modal ยืนยันยังไม่เด้ง)
 * - ตรวจเงื่อนไขอื่นของ HTML ด้วย เช่น pattern, type="email", min/max
 *
 * ในแต่ละหน้าแค่ใส่ `required` ที่ input/select/textarea
 * ข้อความเฉพาะ:     data-required-msg="กรุณาใส่ประเภทสินค้า"
 * ข้อความ pattern:  data-pattern-msg="กรุณาใส่เบอร์โทร 10 หลัก"
 * ต้องตรงกับอีกช่อง:  data-match="id ของช่องนั้น" data-match-msg="รหัสผ่านไม่ตรงกัน"
 * ไม่ต้องตรวจฟอร์มนี้: <form data-no-validate>
 *
 * ⚠ ต้องโหลด "ก่อน" confirm.js เพราะตัวตรวจต้องได้ทำงานก่อน modal ยืนยัน
 */
(function () {
    'use strict';

    var SKIP_TYPES = ['hidden', 'submit', 'button', 'reset', 'image'];

    // ---------- หา label ของช่อง ----------
    function findLabel(el) {
        if (el.id) {
            var byFor = document.querySelector('label[for="' + el.id + '"]');
            if (byFor) return byFor;
        }
        var box = el.closest('.mb-3, .form-group, [class*="col-"], .col');
        return box ? box.querySelector('label') : null;
    }

    function labelText(el) {
        var label = findLabel(el);
        if (!label) return '';
        var clone = label.cloneNode(true);
        clone.querySelectorAll('.req-star, small, .form-text').forEach(function (n) { n.remove(); });
        return clone.textContent.replace(/\*/g, '').replace(/\(.*?\)/g, '').replace(/[:：]\s*$/, '').trim();
    }

    // ช่องค้นหาแบบ searchable-select ใช้ input ที่มองเห็นแทน select ที่ซ่อนอยู่
    function visualOf(el) { return el.__visual || el; }
    function anchorOf(el) { return el.closest('.ss-wrap') || el.closest('.input-group') || el; }

    function addStar(el) {
        var label = findLabel(el);
        // ข้ามถ้า label มีดาวอยู่แล้ว (บางหน้าใส่ * เองไว้ก่อน)
        if (label && !label.querySelector('.req-star') && label.textContent.indexOf('*') === -1) {
            var star = document.createElement('span');
            star.className = 'req-star';
            star.textContent = '*';
            label.appendChild(star);
        }
    }

    // 2026-10-17 → 17/10/2026
    function thaiDate(iso) {
        var p = String(iso).split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
    }

    // ---------- ข้อความภาษาไทย ----------
    function messageFor(el) {
        var v = el.validity;
        var name = labelText(el);

        if (el.__mismatch) return el.dataset.matchMsg || 'ข้อมูลทั้งสองช่องไม่ตรงกัน';
        if (v.valueMissing || el.__blank) {
            if (el.dataset.requiredMsg) return el.dataset.requiredMsg;
            var verb = (el.tagName === 'SELECT' || el.type === 'radio' || el.type === 'checkbox')
                ? 'กรุณาเลือก' : 'กรุณาใส่';
            return verb + (name || 'ข้อมูล');
        }
        if (v.typeMismatch) {
            if (el.type === 'email') return 'รูปแบบอีเมลไม่ถูกต้อง';
            if (el.type === 'url')   return 'รูปแบบลิงก์ไม่ถูกต้อง';
        }
        if (v.patternMismatch) return el.dataset.patternMsg || 'รูปแบบ' + (name || 'ข้อมูล') + 'ไม่ถูกต้อง';
        if (v.tooShort)        return 'ต้องมีอย่างน้อย ' + el.minLength + ' ตัวอักษร';
        if (v.tooLong)         return 'ใส่ได้ไม่เกิน ' + el.maxLength + ' ตัวอักษร';
        if (v.rangeUnderflow)  return el.type === 'date' ? 'วันที่ต้องไม่ก่อน ' + thaiDate(el.min) : 'ค่าต้องไม่น้อยกว่า ' + el.min;
        if (v.rangeOverflow)   return el.type === 'date' ? 'วันที่ต้องไม่เกิน ' + thaiDate(el.max) : 'ค่าต้องไม่เกิน ' + el.max;
        if (v.stepMismatch)    return 'ค่าที่ใส่ไม่ถูกต้อง';
        if (v.badInput)        return 'กรุณาใส่เป็นตัวเลข';
        return el.validationMessage;
    }

    // ---------- ตรวจทีละช่อง ----------
    function shouldCheck(el) {
        return el.willValidate
            && SKIP_TYPES.indexOf(el.type) === -1
            // ช่องพิมพ์ค้นหาของ searchable-select ตรวจผ่าน select ตัวจริงแทน
            && !el.hasAttribute('data-ss-visual')
            // ส่วนที่ซ่อนอยู่ (เช่น กล่องบันทึกการซ่อมตอนสถานะไม่ใช่ "ซ่อมบำรุง") ไม่ต้องตรวจ
            && visualOf(el).getClientRects().length > 0;
    }

    function feedbackOf(el) {
        if (!el.__fb) {
            // แถวที่ถูก clone มาจะติดกล่องข้อความของแถวต้นแบบมาด้วย ใช้กล่องนั้นต่อเลย
            var next = anchorOf(el).nextElementSibling;
            if (next && next.classList.contains('fv-msg')) {
                el.__fb = next;
                return next;
            }
            var fb = document.createElement('div');
            fb.className = 'invalid-feedback fv-msg';
            anchorOf(el).insertAdjacentElement('afterend', fb);
            el.__fb = fb;
        }
        return el.__fb;
    }

    function check(el) {
        // ห้ามกรอกเป็นช่องว่างล้วน ๆ ในช่องที่บังคับ
        el.__blank = el.required && el.tagName !== 'SELECT'
            && typeof el.value === 'string' && el.value !== '' && el.value.trim() === '';

        // data-match="id ของอีกช่อง" → ต้องพิมพ์ให้ตรงกัน (เช่น ยืนยันรหัสผ่าน)
        var other = el.dataset.match ? document.getElementById(el.dataset.match) : null;
        el.__mismatch = !!other && el.value !== '' && el.value !== other.value;

        var ok = el.checkValidity() && !el.__blank && !el.__mismatch;
        var visual = visualOf(el);

        visual.classList.toggle('is-invalid', !ok);
        if (!ok) {
            var fb = feedbackOf(el);
            fb.textContent = messageFor(el);
            fb.classList.add('d-block');
        } else if (el.__fb) {
            el.__fb.classList.remove('d-block');
        }
        return ok;
    }

    // ---------- เตรียมฟอร์ม ----------
    function prepareForm(form) {
        if (form.hasAttribute('data-no-validate')) return;

        form.noValidate = true; // ปิดกล่องเตือนภาษาอังกฤษของ browser ใช้ของเราแทน

        Array.prototype.forEach.call(form.elements, function (el) {
            if (el.__fvReady || !el.tagName || SKIP_TYPES.indexOf(el.type) !== -1) return;
            el.__fvReady = true;

            // แถวที่ clone มาจากแถวที่เคยขึ้นกรอบแดง → ล้างสถานะเดิมออกก่อน
            var stale = anchorOf(el).nextElementSibling;
            if (stale && stale.classList.contains('fv-msg')) {
                stale.classList.remove('d-block');
                visualOf(el).classList.remove('is-invalid');
            }

            if (el.required) addStar(el);

            // หลังกดบันทึกครั้งแรก แก้ช่องไหน กรอบแดงช่องนั้นหายทันที
            ['input', 'change'].forEach(function (ev) {
                el.addEventListener(ev, function () {
                    if (form.dataset.fvSubmitted && shouldCheck(el)) check(el);
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(prepareForm);

        // แถวที่ JS เพิ่มเข้ามาทีหลัง (เช่น รายการสินค้า, ช่วงปลายทาง) ให้ติดดาวด้วย
        var pending = false;
        new MutationObserver(function () {
            if (pending) return;
            pending = true;
            setTimeout(function () {
                pending = false;
                document.querySelectorAll('form').forEach(prepareForm);
            }, 50);
        }).observe(document.body, { childList: true, subtree: true });
    });

    // capture = ได้ทำงานก่อน listener ของ confirm.js
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-validate')) return;

        prepareForm(form); // เผื่อแถวที่เพิ่มด้วย JS ทีหลัง (เช่น รายการสินค้า)
        form.dataset.fvSubmitted = '1';

        var first = null;
        Array.prototype.forEach.call(form.elements, function (el) {
            if (shouldCheck(el) && !check(el) && !first) first = el;
        });

        if (first) {
            e.preventDefault();
            e.stopImmediatePropagation(); // ไม่ให้ modal ยืนยันเด้งขึ้นมา
            var v = visualOf(first);
            v.scrollIntoView({ behavior: 'smooth', block: 'center' });
            v.focus({ preventScroll: true });
        }
    }, true);

    // ---------- ช่องที่พิมพ์ได้เฉพาะตัวเลข ----------
    // <input data-digits-only> เช่น เลขบัตรประชาชน เบอร์โทร รหัสไปรษณีย์
    // พิมพ์/วางตัวอักษรอื่นเข้ามา จะถูกตัดทิ้งทันที (ไม่ต้องรอกดบันทึก)
    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el.matches || !el.matches('input[data-digits-only]')) return;

        var cleaned = el.value.replace(/\D+/g, '');
        if (cleaned === el.value) return;

        var pos = el.selectionStart - (el.value.length - cleaned.length);
        el.value = cleaned;
        try { el.setSelectionRange(Math.max(pos, 0), Math.max(pos, 0)); } catch (err) { /* บาง type ไม่รองรับ */ }
    }, true);

    // กันตั้งแต่ตอนกดแป้น (ยกเว้นปุ่มควบคุม เช่น Backspace ลูกศร Ctrl+V)
    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (!el.matches || !el.matches('input[data-digits-only]')) return;
        if (e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    }, true);

    // ให้หน้าที่เพิ่มช่อง required ทีหลังเรียกติดดาวเองได้
    window.refreshRequiredStars = function (root) {
        (root || document).querySelectorAll('form').forEach(prepareForm);
    };
})();
