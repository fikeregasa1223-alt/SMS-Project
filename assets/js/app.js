/* ==========================================================================
   SMS - App JS v1.0
   ========================================================================== */
(function () {
    'use strict';

    // ------------------------------------------------------------
    // Theme (light / dark)
    // ------------------------------------------------------------
    const THEME_KEY = 'sms-theme';
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
        document.querySelectorAll('[data-theme-toggle] i').forEach(i => {
            i.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        });
    }
    function initTheme() {
        const saved = localStorage.getItem(THEME_KEY);
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(saved || (prefersDark ? 'dark' : 'light'));
    }

    // ------------------------------------------------------------
    // Sidebar
    // ------------------------------------------------------------
    const SIDEBAR_KEY = 'sms-sidebar-collapsed';
    function initSidebar() {
        const body = document.body;

        if (localStorage.getItem(SIDEBAR_KEY) === '1' && window.innerWidth >= 992) {
            body.classList.add('sidebar-collapsed');
        }

        document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    body.classList.toggle('sidebar-open');
                } else {
                    body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem(SIDEBAR_KEY, body.classList.contains('sidebar-collapsed') ? '1' : '0');
                }
            });
        });

        document.querySelectorAll('.sidebar-backdrop').forEach(bd => {
            bd.addEventListener('click', () => body.classList.remove('sidebar-open'));
        });
    }

    // ------------------------------------------------------------
    // Password visibility toggle
    // ------------------------------------------------------------
    function initPwdToggles() {
        document.querySelectorAll('[data-toggle-pwd]').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = document.querySelector(btn.getAttribute('data-toggle-pwd'));
                if (!target) return;
                const show = target.type === 'password';
                target.type = show ? 'text' : 'password';
                const i = btn.querySelector('i');
                if (i) i.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });
    }

    // ------------------------------------------------------------
    // Password strength meter
    // ------------------------------------------------------------
    function scorePassword(pwd) {
        let s = 0;
        if (!pwd) return 0;
        if (pwd.length >= 8) s++;
        if (pwd.length >= 12) s++;
        if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) s++;
        if (/[0-9]/.test(pwd)) s++;
        if (/[^A-Za-z0-9]/.test(pwd)) s++;
        return Math.min(4, s);
    }
    function initPwdMeters() {
        document.querySelectorAll('[data-pwd-meter]').forEach(input => {
            const targetSel = input.getAttribute('data-pwd-meter');
            const meter = document.querySelector(targetSel);
            if (!meter) return;
            input.addEventListener('input', () => {
                const level = scorePassword(input.value);
                meter.className = 'pwd-meter level-' + level;
            });
        });
    }

    // ------------------------------------------------------------
    // Confirm before destructive actions
    // ------------------------------------------------------------
    function initConfirms() {
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', (ev) => {
                const msg = el.getAttribute('data-confirm') || 'Are you sure?';
                if (!window.confirm(msg)) ev.preventDefault();
            });
        });
    }

    // ------------------------------------------------------------
    // Auto-dismiss flash alerts
    // ------------------------------------------------------------
    function initAlerts() {
        document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
            setTimeout(() => {
                el.style.transition = 'opacity .4s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 450);
            }, 5000);
        });
    }

    // ------------------------------------------------------------
    // Bootstrap client-side validation
    // ------------------------------------------------------------
    function initValidation() {
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', (ev) => {
                if (!form.checkValidity()) {
                    ev.preventDefault();
                    ev.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }

    // ------------------------------------------------------------
    // Boot
    // ------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        initTheme();
        initSidebar();
        initPwdToggles();
        initPwdMeters();
        initConfirms();
        initAlerts();
        initValidation();

        document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const cur = document.documentElement.getAttribute('data-theme') || 'light';
                applyTheme(cur === 'dark' ? 'light' : 'dark');
            });
        });
    });
})();