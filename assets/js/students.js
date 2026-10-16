/* ==========================================================================
   SMS - Students module JS
   ========================================================================== */
(function () {
    'use strict';

    // ------------------------------------------------
    // Photo preview on file select
    // ------------------------------------------------
    function initPhotoPreview() {
        document.querySelectorAll('[data-photo-input]').forEach(input => {
            const targetSel = input.getAttribute('data-photo-input');
            const wrap      = document.querySelector(targetSel);
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
                reader.onload = (e) => {
                    wrap.innerHTML = '<img src="' + e.target.result + '" alt="">';
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // ------------------------------------------------
    // Tab switching (URL-hash based)
    // ------------------------------------------------
    function initTabs() {
        const links  = document.querySelectorAll('.nav-tabs-modern .tab-link');
        const panels = document.querySelectorAll('.tab-panel');
        if (!links.length) return;

        function activate(hash) {
            const id = (hash || '#overview').replace('#', '');
            links.forEach(a  => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
            panels.forEach(p => p.classList.toggle('active', p.id === 'tab-' + id));
        }

        links.forEach(a => {
            a.addEventListener('click', (ev) => {
                ev.preventDefault();
                const hash = a.getAttribute('href');
                history.replaceState(null, '', hash);
                activate(hash);
            });
        });

        activate(window.location.hash);
        window.addEventListener('hashchange', () => activate(window.location.hash));
    }

    // ------------------------------------------------
    // Confirm remove guardian chip
    // ------------------------------------------------
    function initRemoveGuardian() {
        document.querySelectorAll('[data-remove-guardian]').forEach(btn => {
            btn.addEventListener('click', (ev) => {
                if (!window.confirm('Remove this guardian from the student?')) {
                    ev.preventDefault();
                }
            });
        });
    }

    // ------------------------------------------------
    // Boot
    // ------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        initPhotoPreview();
        initTabs();
        initRemoveGuardian();
    });
})();