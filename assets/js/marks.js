/* ==========================================================================
   SMS - Marks entry helpers
   ========================================================================== */
(function () {
    'use strict';

    // ----------------------------------------------------------------
    // Auto-calculate percentage on input
    // ----------------------------------------------------------------
    function recomputeRow(row) {
        const scoreInput = row.querySelector('.marks-input');
        const pctCell    = row.querySelector('.marks-pct-cell');
        const maxEl      = row.querySelector('.marks-max');
        const letterCell = row.querySelector('.marks-letter-cell');

        if (!scoreInput || !pctCell || !maxEl) return;

        const score = parseFloat(scoreInput.value);
        const max   = parseFloat(maxEl.dataset.max || maxEl.textContent) || 100;

        if (isNaN(score)) {
            pctCell.textContent = '—';
            if (letterCell) letterCell.innerHTML = '—';
            return;
        }

        const pct = Math.max(0, Math.min(100, (score / max) * 100));
        pctCell.textContent = pct.toFixed(2) + '%';

        // Client-side grade preview (server recalculates authoritative)
        if (letterCell) {
            let letter = null;
            if (pct >= 90) letter = 'A+';
            else if (pct >= 80) letter = 'A';
            else if (pct >= 70) letter = 'B+';
            else if (pct >= 60) letter = 'B';
            else if (pct >= 50) letter = 'C';
            else if (pct >= 40) letter = 'D';
            else letter = 'F';
            letterCell.innerHTML = '<span class="badge bg-light text-dark border fw-bold">' + letter + '</span>';
        }
    }

    // ----------------------------------------------------------------
    // Enter jumps to next input
    // ----------------------------------------------------------------
    function initEnterNav() {
        const inputs = Array.from(document.querySelectorAll('.marks-input'));
        inputs.forEach((inp, idx) => {
            inp.addEventListener('keydown', (ev) => {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    const next = inputs[idx + 1];
                    if (next) next.focus();
                    else document.getElementById('saveMarksBtn')?.focus();
                }
            });
            inp.addEventListener('input', () => {
                const row = inp.closest('tr');
                if (row) {
                    row.classList.remove('table-danger');
                    recomputeRow(row);
                }
            });
        });
    }

    // ----------------------------------------------------------------
    // Absent toggle
    // ----------------------------------------------------------------
    function initAbsentToggle() {
        document.querySelectorAll('[data-absent-toggle]').forEach(cb => {
            const row = cb.closest('tr');
            const input = row?.querySelector('.marks-input');
            if (!row || !input) return;
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    input.value = '';
                    input.disabled = true;
                    input.classList.add('is-absent');
                } else {
                    input.disabled = false;
                    input.classList.remove('is-absent');
                }
                recomputeRow(row);
            });
            if (cb.checked) {
                input.value = '';
                input.disabled = true;
                input.classList.add('is-absent');
            }
        });
    }

    // ----------------------------------------------------------------
    // Validate before submit
    // ----------------------------------------------------------------
    function initSaveValidation() {
        const form = document.getElementById('marksForm');
        if (!form) return;
        form.addEventListener('submit', (ev) => {
            let hasError = false;
            form.querySelectorAll('.marks-input:not([disabled])').forEach(inp => {
                const row = inp.closest('tr');
                const max = parseFloat(row.querySelector('.marks-max')?.dataset.max || '100');
                const val = parseFloat(inp.value);
                if (inp.value !== '' && (isNaN(val) || val < 0 || val > max)) {
                    row.classList.add('table-danger');
                    hasError = true;
                }
            });
            if (hasError) {
                ev.preventDefault();
                if (window.smsToast) window.smsToast('Some scores are invalid (negative or over the maximum).', 'danger');
            } else {
                const btn = document.getElementById('saveMarksBtn');
                if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…'; }
            }
        });
    }

    // ----------------------------------------------------------------
    // Boot
    // ----------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.marks-grid tbody tr').forEach(recomputeRow);
        initEnterNav();
        initAbsentToggle();
        initSaveValidation();
    });
})();