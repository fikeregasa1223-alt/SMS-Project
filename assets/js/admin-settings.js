/* ==========================================================================
   SMS - Admin settings helpers
   ========================================================================== */
(function () {
    'use strict';

    // ------------------------------------------------------------
    // Logo preview
    // ------------------------------------------------------------
    function initLogoPreview() {
        document.querySelectorAll('[data-logo-input]').forEach(input => {
            const wrap = document.querySelector(input.getAttribute('data-logo-input'));
            if (!wrap) return;

            wrap.addEventListener('click', () => input.click());

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) return;

                if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                    if (window.smsToast) window.smsToast('Please choose a JPG, PNG, or WEBP image.', 'danger');
                    input.value = ''; return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    if (window.smsToast) window.smsToast('Image must be under 2 MB.', 'danger');
                    input.value = ''; return;
                }

                const reader = new FileReader();
                reader.onload = (e) => { wrap.innerHTML = '<img src="' + e.target.result + '" alt=""><div class="hint">Change</div>'; };
                reader.readAsDataURL(file);
            });
        });
    }

    // ------------------------------------------------------------
    // Password strength meter (for reset-password)
    // ------------------------------------------------------------
    function initPasswordStrength() {
        document.querySelectorAll('[data-strength-input]').forEach(input => {
            const meter = document.querySelector(input.getAttribute('data-strength-input'));
            if (!meter) return;
            function score(pwd) {
                let s = 0;
                if (pwd.length >= 8) s++;
                if (pwd.length >= 12) s++;
                if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) s++;
                if (/[0-9]/.test(pwd)) s++;
                if (/[^A-Za-z0-9]/.test(pwd)) s++;
                return Math.min(4, s);
            }
            input.addEventListener('input', () => {
                meter.className = 'pwd-meter level-' + score(input.value);
            });
        });
    }

    // ------------------------------------------------------------
    // Permission group: select all
    // ------------------------------------------------------------
    function initPermissionToggles() {
        document.querySelectorAll('[data-toggle-group]').forEach(cb => {
            cb.addEventListener('change', () => {
                const target = cb.getAttribute('data-toggle-group');
                document.querySelectorAll('[data-perm-group="' + target + '"]').forEach(el => {
                    el.checked = cb.checked;
                });
            });
        });
    }

    // ------------------------------------------------------------
    // Confirm dialogs (already in app.js, reinforced here)
    // ------------------------------------------------------------
    function initConfirm() {
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', (ev) => {
                if (!window.confirm(el.getAttribute('data-confirm') || 'Are you sure?')) {
                    ev.preventDefault();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initLogoPreview();
        initPasswordStrength();
        initPermissionToggles();
        initConfirm();
    });
})();