(function () {
    'use strict';

    var SKIP_TYPES = ['hidden', 'submit', 'button', 'reset', 'image'];

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

    function visualOf(el) { return el.__visual || el; }
    function anchorOf(el) { return el.closest('.ss-wrap') || el.closest('.input-group') || el; }

    function addStar(el) {
        var label = findLabel(el);
        if (label && !label.querySelector('.req-star') && label.textContent.indexOf('*') === -1) {
            var star = document.createElement('span');
            star.className = 'req-star';
            star.textContent = '*';
            label.appendChild(star);
        }
    }

    function thaiDate(iso) {
        var p = String(iso).split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
    }

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
        if (v.stepMismatch)    return el.dataset.stepMsg || 'ค่าที่ใส่ไม่ถูกต้อง';
        if (v.badInput)        return 'กรุณาใส่เป็นตัวเลข';
        return el.validationMessage;
    }

    function shouldCheck(el) {
        return el.willValidate
            && SKIP_TYPES.indexOf(el.type) === -1
            && !el.hasAttribute('data-ss-visual')
            && visualOf(el).getClientRects().length > 0;
    }

    function feedbackOf(el) {
        if (!el.__fb || !el.__fb.isConnected) {
            el.__fb = null;
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
        el.__blank = el.required && el.tagName !== 'SELECT'
            && typeof el.value === 'string' && el.value !== '' && el.value.trim() === '';

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

    function prepareForm(form) {
        if (form.hasAttribute('data-no-validate')) return;

        form.noValidate = true;

        Array.prototype.forEach.call(form.elements, function (el) {
            if (el.__fvReady || !el.tagName || SKIP_TYPES.indexOf(el.type) !== -1) return;
            el.__fvReady = true;

            var stale = anchorOf(el).nextElementSibling;
            if (stale && stale.classList.contains('fv-msg')) {
                stale.classList.remove('d-block');
                visualOf(el).classList.remove('is-invalid');
            }

            if (el.required) addStar(el);

            ['input', 'change'].forEach(function (ev) {
                el.addEventListener(ev, function () {
                    if (!shouldCheck(el)) return;
                    if (form.dataset.fvSubmitted) { check(el); return; }

                    if (el.dataset.stepMsg) {
                        if (el.validity.stepMismatch) check(el);
                        else if (visualOf(el).classList.contains('is-invalid')) {
                            visualOf(el).classList.remove('is-invalid');
                            if (el.__fb) el.__fb.classList.remove('d-block');
                        }
                    }
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(prepareForm);

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

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-validate')) return;

        prepareForm(form);
        form.dataset.fvSubmitted = '1';

        var first = null;
        Array.prototype.forEach.call(form.elements, function (el) {
            if (shouldCheck(el) && !check(el) && !first) first = el;
        });

        if (first) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var v = visualOf(first);
            v.scrollIntoView({ behavior: 'smooth', block: 'center' });
            v.focus({ preventScroll: true });
        }
    }, true);

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el.matches || !el.matches('input[data-digits-only]')) return;

        var cleaned = el.value.replace(/\D+/g, '');
        if (cleaned === el.value) return;

        var pos = el.selectionStart - (el.value.length - cleaned.length);
        el.value = cleaned;
        try { el.setSelectionRange(Math.max(pos, 0), Math.max(pos, 0)); } catch (err) { }
    }, true);

    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (!el.matches || !el.matches('input[data-digits-only]')) return;
        if (e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    }, true);

    window.refreshRequiredStars = function (root) {
        (root || document).querySelectorAll('form').forEach(prepareForm);
    };
})();
