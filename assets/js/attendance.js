/* ==========================================================================
   SMS - Attendance module JS
   ========================================================================== */
(function () {
    'use strict';

    // ----------------------------------------------------------------
    // Mark all present / absent / late / excused
    // ----------------------------------------------------------------
    function applyStatusToAll(status) {
        document.querySelectorAll('input[type="radio"][name^="status["]').forEach(radio => {
            if (radio.value === status) {
                radio.checked = true;
            }
        });
        updateSummary();
    }

    // ----------------------------------------------------------------
    // Live summary update
    // ----------------------------------------------------------------
    function updateSummary() {
        const counts = { present: 0, absent: 0, late: 0, excused: 0 };
        document.querySelectorAll('input[type="radio"][name^="status["]:checked').forEach(r => {
            if (counts[r.value] !== undefined) counts[r.value]++;
        });

        document.querySelectorAll('[data-att-count]').forEach(el => {
            const s = el.getAttribute('data-att-count');
            el.textContent = counts[s] ?? 0;
        });

        const total = Object.values(counts).reduce((a, b) => a + b, 0);
        const done  = counts.present + counts.absent + counts.late + counts.excused;
        const sumEl = document.getElementById('attTotal');
        if (sumEl) sumEl.textContent = total;
    }

    // ----------------------------------------------------------------
    // Keyboard shortcuts (P/A/L/E) for fast attendance
    // ----------------------------------------------------------------
    function initKeyboardShortcuts() {
        const list = document.getElementById('attList');
        if (!list) return;

        let currentIndex = 0;
        const rows = Array.from(list.querySelectorAll('.att-row'));

        function focusRow(i) {
            rows.forEach((r, idx) => r.classList.toggle('keyboard-focus', idx === i));
            if (rows[i]) rows[i].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        document.addEventListener('keydown', (ev) => {
            // Ignore when typing in inputs
            if (['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) return;
            if (rows.length === 0) return;

            const key = ev.key.toLowerCase();
            const map = { p: 'present', a: 'absent', l: 'late', e: 'excused' };

            if (key === 'j' || ev.key === 'ArrowDown') {
                currentIndex = Math.min(rows.length - 1, currentIndex + 1);
                focusRow(currentIndex); ev.preventDefault();
            } else if (key === 'k' || ev.key === 'ArrowUp') {
                currentIndex = Math.max(0, currentIndex - 1);
                focusRow(currentIndex); ev.preventDefault();
            } else if (map[key]) {
                const radio = rows[currentIndex]?.querySelector('input[value="' + map[key] + '"]');
                if (radio) { radio.checked = true; updateSummary(); }
                currentIndex = Math.min(rows.length - 1, currentIndex + 1);
                focusRow(currentIndex);
                ev.preventDefault();
            }
        });

        focusRow(0);
    }

    // ----------------------------------------------------------------
    // Boot
    // ----------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        // Mark-all buttons
        document.querySelectorAll('[data-mark-all]').forEach(btn => {
            btn.addEventListener('click', () => applyStatusToAll(btn.getAttribute('data-mark-all')));
        });

        // Radio changes → update summary
        document.querySelectorAll('input[type="radio"][name^="status["]').forEach(r => {
            r.addEventListener('change', updateSummary);
        });

        updateSummary();
        initKeyboardShortcuts();
    });
})();