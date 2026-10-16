/* ==========================================================================
   SMS - Reusable UI components
   ========================================================================== */
(function () {
    'use strict';

    // ------------------------------------------------
    // Confirm-delete via data-confirm already exists in app.js
    // ------------------------------------------------

    // ------------------------------------------------
    // Table live search (client-side quick filter)
    // ------------------------------------------------
    function initTableSearch() {
        document.querySelectorAll('[data-table-search]').forEach(input => {
            const tableId = input.getAttribute('data-table-search');
            const table   = document.getElementById(tableId);
            if (!table) return;

            input.addEventListener('input', () => {
                const q = input.value.trim().toLowerCase();
                table.querySelectorAll('tbody tr').forEach(tr => {
                    const text = tr.textContent.toLowerCase();
                    tr.style.display = q === '' || text.includes(q) ? '' : 'none';
                });
            });
        });
    }

    // ------------------------------------------------
    // Auto-submit filter forms on select change
    // ------------------------------------------------
    function initAutoFilter() {
        document.querySelectorAll('[data-auto-submit]').forEach(el => {
            el.addEventListener('change', () => el.form && el.form.submit());
        });
    }

    // ------------------------------------------------
    // CSRF helper for fetch requests
    // ------------------------------------------------
    window.smsCsrf = function () {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    // ------------------------------------------------
    // Toast helper
    // ------------------------------------------------
    window.smsToast = function (message, tone = 'primary') {
        let wrap = document.getElementById('sms-toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'sms-toast-wrap';
            wrap.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:2000;display:flex;flex-direction:column;gap:.5rem;';
            document.body.appendChild(wrap);
        }
        const el = document.createElement('div');
        el.className = 'alert alert-' + tone + ' shadow-sm mb-0';
        el.style.minWidth = '240px';
        el.textContent = message;
        wrap.appendChild(el);
        setTimeout(() => {
            el.style.transition = 'opacity .3s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 320);
        }, 3000);
    };

    // ------------------------------------------------
    // Boot
    // ------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        initTableSearch();
        initAutoFilter();
    });
})();