/*
 * searchable-select.js — เปลี่ยน <select> ที่มีตัวเลือกเยอะ (เช่น ลูกค้า) ให้พิมพ์ค้นหาได้
 *
 * วิธีใช้: ใส่ data-searchable ที่ <select> เดิม (ไม่ต้องแก้ controller)
 *   <select name="id_customer" id="id_customer" class="form-select" data-searchable
 *           data-placeholder="พิมพ์ชื่อลูกค้าเพื่อค้นหา..." required> ... </select>
 *
 * - <select> ตัวจริงยังอยู่ (ซ่อนไว้) → ค่าที่ส่งไป server เหมือนเดิม, old() ใช้ได้
 * - เลือกแล้วยิง event "change" ที่ select → โค้ด auto-fill เดิมยังทำงาน
 * - ใช้กับ filter หน้า index ได้ด้วย (ลบข้อความทิ้ง = ค่าว่าง = "ทั้งหมด")
 *
 * ⚠ โหลดก่อน form-validation.js
 */
(function () {
    'use strict';

    var MAX_SHOW = 50;

    function init(select) {
        if (select.__ssReady) return;
        select.__ssReady = true;

        var wrap = document.createElement('div');
        wrap.className = 'ss-wrap';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('d-none');

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control' + (select.classList.contains('form-select-sm') ? ' form-control-sm' : '');
        input.autocomplete = 'off';
        input.setAttribute('data-ss-visual', '');
        input.placeholder = select.dataset.placeholder || 'พิมพ์เพื่อค้นหา...';
        if (select.id) {
            input.id = select.id + '_search';
            var lbl = document.querySelector('label[for="' + select.id + '"]');
            if (lbl) lbl.htmlFor = input.id;
        }
        if (select.disabled) input.disabled = true;
        wrap.appendChild(input);
        select.__visual = input;

        var list = document.createElement('div');
        list.className = 'ss-list list-group d-none';
        wrap.appendChild(list);

        var items = [];
        var cursor = -1;

        function options() {
            return Array.prototype.filter.call(select.options, function (o) { return o.value !== ''; });
        }

        function selectedText() {
            var o = select.options[select.selectedIndex];
            return o && o.value !== '' ? o.text.trim() : '';
        }

        function setValue(val) {
            if (select.value === val) return;
            select.value = val;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function render() {
            var term = input.value.trim().toLowerCase();
            list.innerHTML = '';
            items = [];
            cursor = -1;

            var matches = options().filter(function (o) {
                return !term || o.text.toLowerCase().indexOf(term) !== -1;
            });

            matches.slice(0, MAX_SHOW).forEach(function (o) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action py-2';
                btn.textContent = o.text.trim();
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault(); // กันไม่ให้ input blur ก่อนเลือก
                    pick(o);
                });
                list.appendChild(btn);
                items.push({ el: btn, opt: o });
            });

            if (!matches.length) {
                var empty = document.createElement('div');
                empty.className = 'list-group-item text-muted small';
                empty.textContent = 'ไม่พบข้อมูลที่ค้นหา';
                list.appendChild(empty);
            } else if (matches.length > MAX_SHOW) {
                var more = document.createElement('div');
                more.className = 'list-group-item text-muted small';
                more.textContent = 'พบ ' + matches.length + ' รายการ — พิมพ์เพิ่มเพื่อกรองให้แคบลง';
                list.appendChild(more);
            }
            list.classList.remove('d-none');
        }

        function highlight(i) {
            items.forEach(function (it, idx) { it.el.classList.toggle('active', idx === i); });
            if (items[i]) items[i].el.scrollIntoView({ block: 'nearest' });
            cursor = i;
        }

        function pick(opt) {
            input.value = opt.text.trim();
            setValue(opt.value);
            close();
        }

        function close() {
            list.classList.add('d-none');
            cursor = -1;
        }

        input.addEventListener('focus', function () { input.select(); render(); });
        input.addEventListener('input', function () {
            if (input.value.trim() !== selectedText()) setValue(''); // พิมพ์ใหม่ = ยังไม่ได้เลือก
            render();
        });
        input.addEventListener('keydown', function (e) {
            var open = !list.classList.contains('d-none');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!open) render();
                highlight(Math.min(cursor + 1, items.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlight(Math.max(cursor - 1, 0));
            } else if (e.key === 'Enter' && open) {
                e.preventDefault(); // ไม่ submit ฟอร์มตอนกำลังเลือก
                var target = items[cursor] || (items.length === 1 ? items[0] : null);
                if (target) pick(target.opt);
            } else if (e.key === 'Escape') {
                close();
            }
        });
        input.addEventListener('blur', function () {
            // ถ้าพิมพ์ตรงกับชื่อพอดี ให้เลือกให้เลย ไม่งั้นคืนค่าที่เลือกไว้
            var typed = input.value.trim().toLowerCase();
            var exact = options().filter(function (o) { return o.text.trim().toLowerCase() === typed; })[0];
            if (exact) {
                input.value = exact.text.trim();
                setValue(exact.value);
            } else {
                input.value = selectedText();
            }
            close();
        });

        // ถ้าโค้ดอื่นเปลี่ยนค่า select เอง (เช่น auto-fill) ให้ช่องค้นหาตามด้วย
        select.addEventListener('change', function () {
            if (document.activeElement !== input) input.value = selectedText();
        });

        input.value = selectedText(); // รองรับ old() / ค่าเดิมตอนแก้ไข
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select[data-searchable]').forEach(init);
    });

    window.initSearchableSelect = init; // สำหรับ select ที่สร้างทีหลังด้วย JS
})();
