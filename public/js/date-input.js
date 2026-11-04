(function () {
    'use strict';

    function init(el) {
        if (el.__fp || !window.flatpickr) return;

        var inModal = !!el.closest('.modal');

        var fp = window.flatpickr(el, {
            locale: (window.flatpickr.l10ns && window.flatpickr.l10ns.th) || 'default',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            altInputClass: el.className,
            allowInput: true,
            disableMobile: true,
            minDate: el.min || null,
            maxDate: el.max || null,
            static: inModal,
            parseDate: function (str, format) {
                var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(str.trim());
                if (m) return new Date(+m[3], +m[2] - 1, +m[1]);
                return window.flatpickr.parseDate(str, format);
            },
        });

        el.__fp = fp;

        var alt = fp.altInput;
        if (!alt) return;

        alt.placeholder = el.placeholder || 'วว/ดด/ปปปป';
        if (el.dataset.requiredMsg) alt.dataset.requiredMsg = el.dataset.requiredMsg;
        if (el.getAttribute('aria-label')) alt.setAttribute('aria-label', el.getAttribute('aria-label'));
        if (el.id) {
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
