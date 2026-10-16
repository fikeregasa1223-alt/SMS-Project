/* ==========================================================================
   SMS - Dashboard charts (Chart.js)
   Requires: Chart.js loaded before this script
   ========================================================================== */
(function () {
    'use strict';

    if (typeof Chart === 'undefined') return;

    // Global defaults aligned with design system
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#64748b';

    const primary = '#4f46e5';
    const success = '#10b981';
    const warning = '#f59e0b';
    const danger  = '#ef4444';
    const info    = '#06b6d4';

    function readData(el, key) {
        try {
            return JSON.parse(el.getAttribute('data-' + key) || '[]');
        } catch (e) {
            return [];
        }
    }

    // ----------------------------------------------------------------
    // Enrollment trend (line)
    // ----------------------------------------------------------------
    const enrCanvas = document.getElementById('chartEnrollment');
    if (enrCanvas) {
        const labels = readData(enrCanvas, 'labels');
        const values = readData(enrCanvas, 'values');
        new Chart(enrCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Students',
                    data: values,
                    borderColor: primary,
                    backgroundColor: 'rgba(79,70,229,.12)',
                    fill: true,
                    tension: .35,
                    borderWidth: 2,
                    pointBackgroundColor: primary,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.15)' }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ----------------------------------------------------------------
    // Attendance overview (doughnut)
    // ----------------------------------------------------------------
    const attCanvas = document.getElementById('chartAttendance');
    if (attCanvas) {
        const present = parseInt(attCanvas.getAttribute('data-present') || '0', 10);
        const absent  = parseInt(attCanvas.getAttribute('data-absent')  || '0', 10);
        const late    = parseInt(attCanvas.getAttribute('data-late')    || '0', 10);
        const excused = parseInt(attCanvas.getAttribute('data-excused') || '0', 10);

        new Chart(attCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    data: [present, absent, late, excused],
                    backgroundColor: [success, danger, warning, info],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, usePointStyle: true } }
                }
            }
        });
    }
    // ----------------------------------------------------------------
    // Attendance rate (line) — attendance overview
    // ----------------------------------------------------------------
    const attRateCanvas = document.getElementById('chartAttRate');
    if (attRateCanvas) {
        const labels = readData(attRateCanvas, 'labels');
        const values = readData(attRateCanvas, 'values');
        new Chart(attRateCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Attendance %',
                    data: values,
                    borderColor: success,
                    backgroundColor: 'rgba(16,185,129,.12)',
                    fill: true,
                    tension: .35,
                    borderWidth: 2,
                    pointBackgroundColor: success,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(148,163,184,.15)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ----------------------------------------------------------------
    // Attendance today (doughnut) — attendance overview
    // ----------------------------------------------------------------
    const attTodayCanvas = document.getElementById('chartAttToday');
    if (attTodayCanvas) {
        const present = parseInt(attTodayCanvas.getAttribute('data-present') || '0', 10);
        const absent  = parseInt(attTodayCanvas.getAttribute('data-absent')  || '0', 10);
        const late    = parseInt(attTodayCanvas.getAttribute('data-late')    || '0', 10);
        const excused = parseInt(attTodayCanvas.getAttribute('data-excused') || '0', 10);

        new Chart(attTodayCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    data: [present, absent, late, excused],
                    backgroundColor: [success, danger, warning, info],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, usePointStyle: true } }
                }
            }
        });
    }
    // ----------------------------------------------------------------
    // Performance by grade (bar)
    // ----------------------------------------------------------------
    const perfCanvas = document.getElementById('chartPerformance');
    if (perfCanvas) {
        const labels = readData(perfCanvas, 'labels');
        const values = readData(perfCanvas, 'values');
        new Chart(perfCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Avg %',
                    data: values,
                    backgroundColor: 'rgba(79,70,229,.85)',
                    borderRadius: 6,
                    maxBarThickness: 40,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(148,163,184,.15)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ----------------------------------------------------------------
    // Student distribution by grade (doughnut)
    // ----------------------------------------------------------------
    const distCanvas = document.getElementById('chartDistribution');
    if (distCanvas) {
        const labels = readData(distCanvas, 'labels');
        const values = readData(distCanvas, 'values');
        new Chart(distCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: [primary, success, warning, info],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, usePointStyle: true } }
                }
            }
        });
    }
})();