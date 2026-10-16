<?php
declare(strict_types=1);

/**
 * SMS — Sidebar navigation
 * Path: includes/sidebar.php
 *
 * Role-aware sidebar with active-item highlight.
 * Uses __() for i18n-friendly labels.
 */

$user = current_user();
$role = $user['primary_role'] ?? 'guest';
$schoolName = setting('school_name', 'SMS');
$schoolLogo = setting('school_logo', '');

if (!function_exists('nav_link')) {
    /**
     * Render a sidebar link. $label may be a translation KEY
     * (e.g. "nav.dashboard") or a raw English label.
     */
    function nav_link(string $href, string $icon, string $labelKey, string $match = ''): string
    {
        $match  = $match !== '' ? $match : $href;
        $active = is_active($match) ? 'active' : '';
        $label  = function_exists('__') ? __($labelKey) : $labelKey;

        return '<a class="nav-link ' . $active . '" href="' . e(url($href)) . '">'
             . '<i class="bi ' . e($icon) . '"></i><span>' . e($label) . '</span></a>';
    }
}

/**
 * Small helper for section headers — translated too.
 */
if (!function_exists('nav_section')) {
    function nav_section(string $key): string
    {
        $label = function_exists('__') ? __($key) : $key;
        return '<div class="nav-section">' . e($label) . '</div>';
    }
}
?>
<aside class="sidebar" id="appSidebar">
    <a class="brand" href="<?= e(url(role_home($role))) ?>">
        <?php if ($schoolLogo && file_exists(path('uploads/school/' . $schoolLogo))): ?>
            <img src="<?= e(url('uploads/school/' . $schoolLogo)) ?>" alt="Logo">
        <?php else: ?>
            <span class="logo-fallback"><?= e(mb_substr($schoolName, 0, 1)) ?></span>
        <?php endif; ?>
        <span>
            <span class="brand-name d-block"><?= e(APP_NAME) ?></span>
            <span class="brand-sub d-block"><?= e(APP_FULLNAME) ?></span>
        </span>
    </a>

    <nav class="mt-2 flex-grow-1">

        <?= nav_link(role_home($role), 'bi-speedometer2', 'nav.dashboard', 'dashboard.php') ?>

        <?php if ($role === 'admin'): ?>

            <!-- ============================== ACADEMIC ============================== -->
            <?= nav_section('Academic') ?>
            <?= nav_link('admin/academic-years/index.php',      'bi-calendar3',         'Academic Years',     'academic-years') ?>
            <?= nav_link('admin/terms/index.php',               'bi-calendar-week',     'Terms',              'admin/terms') ?>
            <?= nav_link('admin/grades/index.php',              'bi-mortarboard',       'Grades',             'admin/grades') ?>
            <?= nav_link('admin/sections/index.php',            'bi-diagram-3',         'Sections',           'admin/sections') ?>
            <?= nav_link('admin/subjects/index.php',            'bi-journal-bookmark',  'Subjects',           'admin/subjects') ?>
            <?= nav_link('admin/assignments/index.php',         'bi-person-workspace',  'Class Assignments',  'admin/assignments') ?>

            <!-- ============================== STUDENTS ============================== -->
            <?= nav_section('nav.students') ?>
            <?= nav_link('admin/students/index.php',            'bi-people',            'nav.students',       'admin/students') ?>
            <?= nav_link('admin/enrollments/index.php',         'bi-person-plus',       'Enrollments',        'admin/enrollments') ?>
            <?= nav_link('admin/guardians/index.php',           'bi-people-fill',       'Guardians',          'admin/guardians') ?>
            <?= nav_link('admin/students/reports.php',          'bi-file-earmark-text', 'Student Reports',    'students/reports') ?>

            <!-- ============================== TEACHERS ============================== -->
            <?= nav_section('nav.teachers') ?>
            <?= nav_link('admin/teachers/index.php',            'bi-person-badge',      'nav.teachers',       'admin/teachers') ?>
            <?= nav_link('admin/teacher-assignments/index.php', 'bi-person-check',      'Teacher Assignments','teacher-assignments') ?>

            <!-- ============================== ATTENDANCE ============================== -->
            <?= nav_section('nav.attendance') ?>
            <?= nav_link('admin/attendance/take.php',           'bi-check2-square',     'Take Attendance',    'attendance/take') ?>
            <?= nav_link('admin/attendance/history.php',        'bi-clock-history',     'Attendance History', 'attendance/history') ?>
            <?= nav_link('admin/attendance/reports.php',        'bi-bar-chart',         'Attendance Reports', 'attendance/reports') ?>

            <!-- ============================== EXAMINATIONS ============================== -->
            <?= nav_section('nav.examinations') ?>
            <?= nav_link('admin/examinations/index.php',        'bi-clipboard-check',   'nav.examinations',   'admin/examinations') ?>
            <?= nav_link('admin/examinations/marks.php',        'bi-pencil-square',     'Enter Marks',        'examinations/marks') ?>
            <?= nav_link('admin/examinations/results.php',      'bi-trophy',            'Results',            'examinations/results') ?>
            <?= nav_link('admin/grading/index.php',             'bi-list-check',        'Grading Rules',      'admin/grading') ?>

            <!-- ============================== COMMUNICATION ============================== -->
            <?= nav_section('Communication') ?>
            <?= nav_link('admin/announcements/index.php',       'bi-megaphone',         'Announcements',      'admin/announcements') ?>

            <!-- ============================== REPORTS ============================== -->
            <?= nav_section('Reports') ?>
            <?= nav_link('admin/reports/academic.php',          'bi-graph-up',          'Academic Reports',   'reports/academic') ?>
            <?= nav_link('admin/reports/attendance.php',        'bi-calendar-check',    'Attendance Reports', 'reports/attendance') ?>
            <?= nav_link('admin/reports/students.php',          'bi-people-fill',       'Student Reports',    'reports/students') ?>

            <!-- ============================== ADMINISTRATION ============================== -->
            <?= nav_section('Administration') ?>
            <?= nav_link('admin/users/index.php',               'bi-person-gear',       'Users',              'admin/users') ?>
            <?= nav_link('admin/roles/index.php',               'bi-shield-lock',       'Roles & Permissions','admin/roles') ?>
            <?= nav_link('admin/settings/index.php',            'bi-gear',              'School Settings',    'admin/settings') ?>
            <?= nav_link('admin/audit/index.php',               'bi-list-columns',      'Audit Logs',         'admin/audit') ?>

            <!-- ============================== NEW MODULES ============================== -->
            <?= nav_section('New Modules') ?>
            <?= nav_link('admin/timetable/index.php',           'bi-calendar-week',     'nav.timetable',      'admin/timetable') ?>
            <?= nav_link('admin/library/index.php',             'bi-book',              'nav.library',        'admin/library') ?>
            <?= nav_link('admin/transport/index.php',           'bi-truck',             'nav.transport',      'admin/transport') ?>
            <?= nav_link('admin/analytics/index.php',           'bi-bar-chart-line',    'nav.analytics',      'admin/analytics') ?>
            <?= nav_link('admin/id-cards/index.php',            'bi-person-badge',      'nav.id_cards',       'admin/id-cards') ?>

        <?php elseif ($role === 'teacher'): ?>

            <!-- ============================== TEACHING ============================== -->
            <?= nav_section('Teaching') ?>
            <?= nav_link('teacher/students/index.php',          'bi-people',            'My Students',     'teacher/students') ?>
            <?= nav_link('teacher/classes/index.php',           'bi-diagram-3',         'My Classes',      'teacher/classes') ?>
            <?= nav_link('teacher/attendance/take.php',         'bi-check2-square',     'Take Attendance', 'teacher/attendance') ?>
            <?= nav_link('teacher/marks/index.php',             'bi-pencil-square',     'Enter Marks',     'teacher/marks') ?>

            <!-- ============================== COMMUNICATION ============================== -->
            <?= nav_section('Communication') ?>
            <?= nav_link('teacher/announcements/index.php',     'bi-megaphone',         'Announcements',   'teacher/announcements') ?>

        <?php elseif ($role === 'student'): ?>

            <!-- ============================== MY ACADEMICS ============================== -->
            <?= nav_section('My Academics') ?>
            <?= nav_link('student/attendance.php',              'bi-check2-square',     'My Attendance',   'student/attendance') ?>
            <?= nav_link('student/results.php',                 'bi-trophy',            'My Results',      'student/results') ?>
            <?= nav_link('student/reports.php',                 'bi-file-earmark-text', 'My Reports',      'student/reports') ?>

            <!-- ============================== COMMUNICATION ============================== -->
            <?= nav_section('Communication') ?>
            <?= nav_link('student/announcements.php',           'bi-megaphone',         'Announcements',   'student/announcements') ?>

        <?php elseif ($role === 'parent'): ?>

            <!-- ============================== MY CHILDREN ============================== -->
            <?= nav_section('My Children') ?>
            <?= nav_link('parent/students.php',                 'bi-people',            'Children',        'parent/students') ?>
            <?= nav_link('parent/attendance.php',               'bi-check2-square',     'Attendance',      'parent/attendance') ?>
            <?= nav_link('parent/results.php',                  'bi-trophy',            'Results',         'parent/results') ?>
            <?= nav_link('parent/reports.php',                  'bi-file-earmark-text', 'Reports',         'parent/reports') ?>

            <!-- ============================== COMMUNICATION ============================== -->
            <?= nav_section('Communication') ?>
            <?= nav_link('parent/announcements.php',            'bi-megaphone',         'Announcements',   'parent/announcements') ?>

        <?php endif; ?>

    </nav>
</aside>