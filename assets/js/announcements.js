/* ==========================================================================
   SMS - Announcements JS
   ========================================================================== */
(function () {
    'use strict';

    // ----------------------------------------------------------------
    // Character counter for content
    // ----------------------------------------------------------------
    function initCounter() {
        const ta = document.getElementById('annContent');
        const counter = document.getElementById('annCounter');
        if (!ta || !counter) return;
        function update() { counter.textContent = ta.value.length + ' chars'; }
        ta.addEventListener('input', update);
        update();
    }

    // ----------------------------------------------------------------
    // Show/hide grade & section selectors by audience
    // ----------------------------------------------------------------
    function initAudienceToggle() {
        const aud   = document.getElementById('annAudience');
        const grade = document.getElementById('gradeWrap');
        const sec   = document.getElementById('sectionWrap');
        if (!aud) return;

        function update() {
            const v = aud.value;
            if (grade) grade.style.display = (v === 'grade' || v === 'section') ? '' : 'none';
            if (sec)   sec.style.display   = (v === 'section') ? '' : 'none';
        }
        aud.addEventListener('change', update);
        update();
    }

    // ----------------------------------------------------------------
    // Confirm publish
    // ----------------------------------------------------------------
    function initPublishConfirm() {
        document.querySelectorAll('[data-confirm-publish]').forEach(el => {
            el.addEventListener('click', (ev) => {
                if (!window.confirm('Publish this announcement to the selected audience?')) {
                    ev.preventDefault();
                }
            });
        });
    }

    // ----------------------------------------------------------------
    // Boot
    // ----------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        initCounter();
        initAudienceToggle();
        initPublishConfirm();
    });
})();