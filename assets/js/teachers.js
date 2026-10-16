/* ==========================================================================
   SMS - Teachers module JS
   ========================================================================== */
(function () {
    'use strict';

    // ------------------------------------------------
    // Photo preview (shared with students module)
    // ------------------------------------------------
    function initPhotoPreview() {
        document.querySelectorAll('[data-photo-input]').forEach(input => {
            const wrap = document.querySelector(input.getAttribute('data-photo-input'));
            if (!wrap) return;

            wrap.addEventListener('click', () => input.click());

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) return;

                if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                    if (window.smsToast) window.smsToast('Please choose a JPG, PNG, or WEBP image.', 'danger');
                    input.value = '';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    if (window.smsToast) window.smsToast('Image must be under 2 MB.', 'danger');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => { wrap.innerHTML = '<img src="' + e.target.result + '" alt="">'; };
                reader.readAsDataURL(file);
            });
        });
    }

    // ------------------------------------------------
    // Section filter by grade (assignments)
    // ------------------------------------------------
    function initSectionFilter() {
        const gradeSel = document.getElementById('gradeSelect');
        const secSel   = document.getElementById('sectionSelect');
        if (!gradeSel || !secSel) return;

        function filter() {
            const gid = gradeSel.value;
            Array.from(secSel.options).forEach(opt => {
                if (opt.value === '0') return;
                const g = opt.getAttribute('data-grade');
                opt.hidden = gid !== '0' && g !== gid;
            });
            if (secSel.selectedOptions[0] && secSel.selectedOptions[0].hidden) {
                secSel.value = '0';
            }
        }
        gradeSel.addEventListener('change', filter);
        filter();
    }

    // ------------------------------------------------
    // Boot
    // ------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        initPhotoPreview();
        initSectionFilter();
    });
})();