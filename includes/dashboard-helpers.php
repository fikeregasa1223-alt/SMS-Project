<?php
declare(strict_types=1);

/**
 * SMS — Dashboard helpers (role-agnostic)
 */

if (defined('SMS_DASHBOARD_HELPERS_LOADED')) { return; }
define('SMS_DASHBOARD_HELPERS_LOADED', true);

/**
 * Fetch user row by id (with roles).
 */
function user_by_id(int $id): ?array
{
    $u = Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);
    if (!$u) return null;
    $u['roles']        = load_user_roles($id);
    $u['permissions']  = load_user_permissions($id);
    $u['primary_role'] = $u['roles'][0] ?? 'student';
    return $u;
}

/**
 * Return the student record linked to a user id (or null).
 */
function student_for_user(int $userId): ?array
{
    return Database::fetch("SELECT * FROM students WHERE user_id = ?", [$userId]);
}

/**
 * Return the teacher record linked to a user id (or null).
 */
function teacher_for_user(int $userId): ?array
{
    return Database::fetch("SELECT * FROM teachers WHERE user_id = ?", [$userId]);
}

/**
 * Return the parent record linked to a user id (or null).
 */
function parent_for_user(int $userId): ?array
{
    return Database::fetch("SELECT * FROM parents WHERE user_id = ?", [$userId]);
}

/**
 * Students linked to a parent via parent_students.
 */
function parent_children(int $parentId): array
{
    return Database::fetchAll(
        "SELECT s.*,
                (SELECT g.name FROM enrollments e JOIN grades g ON g.id = e.grade_id
                 WHERE e.student_id = s.id AND e.status='active' LIMIT 1) AS grade_name,
                (SELECT sec.name FROM enrollments e JOIN sections sec ON sec.id = e.section_id
                 WHERE e.student_id = s.id AND e.status='active' LIMIT 1) AS section_name
         FROM parent_students ps
         JOIN students s ON s.id = ps.student_id
         WHERE ps.parent_id = ?
         ORDER BY s.first_name ASC, s.last_name ASC",
        [$parentId]
    );
}

/**
 * Convenience: attendance summary for a student.
 */
function student_attendance_summary(int $studentId): array
{
    $row = Database::fetch(
        "SELECT COUNT(*) AS total,
                SUM(status='present') AS present,
                SUM(status='absent')  AS absent,
                SUM(status='late')    AS late,
                SUM(status='excused') AS excused
         FROM attendance WHERE student_id = ?",
        [$studentId]
    ) ?: ['total'=>0,'present'=>0,'absent'=>0,'late'=>0,'excused'=>0];

    $tot = (int)$row['total'];
    $ok  = (int)$row['present'] + (int)$row['late'] + (int)$row['excused'];
    $row['percentage'] = $tot > 0 ? round(($ok / $tot) * 100, 1) : 0;
    return $row;
}

/**
 * Latest published exam results for a student (grouped by exam).
 */
function student_latest_results(int $studentId, int $limit = 3): array
{
    $rows = Database::fetchAll(
        "SELECT m.id, m.score, m.max_mark, m.percentage, m.grade_letter, m.grade_point,
                sub.name AS subject_name, sub.code AS subject_code,
                ex.name AS exam_name, ex.id AS exam_id, ex.type AS exam_type
         FROM marks m
         JOIN examinations ex ON ex.id = m.examination_id
         JOIN subjects sub ON sub.id = m.subject_id
         WHERE m.student_id = ? AND ex.status IN ('published','completed')
         ORDER BY m.id DESC LIMIT 30",
        [$studentId]
    );

    $grouped = [];
    foreach ($rows as $r) {
        $grouped[$r['exam_id']]['exam_name'] = $r['exam_name'];
        $grouped[$r['exam_id']]['exam_type'] = $r['exam_type'];
        $grouped[$r['exam_id']]['marks'][]   = $r;
    }
    return array_slice($grouped, 0, $limit, true);
}

/**
 * Top-level performance card data for a student.
 */
function student_performance_overview(int $studentId): array
{
    $row = Database::fetch(
        "SELECT AVG(percentage) AS avg_pct,
                COUNT(*) AS marks_count,
                MAX(percentage) AS best,
                MIN(percentage) AS worst
         FROM marks WHERE student_id = ? AND percentage IS NOT NULL",
        [$studentId]
    ) ?: ['avg_pct'=>0,'marks_count'=>0,'best'=>0,'worst'=>0];

    return [
        'avg_pct'     => round((float)$row['avg_pct'], 1),
        'marks_count' => (int)$row['marks_count'],
        'best'        => round((float)$row['best'], 1),
        'worst'       => round((float)$row['worst'], 1),
    ];
}

/**
 * Recent activity for a student.
 */
function student_recent_activity(int $studentId, int $limit = 5): array
{
    return Database::fetchAll(
        "SELECT action, description, created_at
         FROM audit_logs
         WHERE entity = 'students' AND entity_id = ?
         ORDER BY id DESC LIMIT ?",
        [$studentId, $limit]
    );
}

/**
 * Teacher assigned sections (unique) for a year.
 */
function teacher_assigned_sections(int $teacherId, ?int $yearId = null): array
{
    $sql = "SELECT DISTINCT ta.section_id, ta.academic_year_id, ta.grade_id,
                   sec.name AS section_name, g.name AS grade_name,
                   ay.name AS year_name
            FROM teacher_assignments ta
            JOIN sections sec ON sec.id = ta.section_id
            JOIN grades   g   ON g.id = ta.grade_id
            JOIN academic_years ay ON ay.id = ta.academic_year_id
            WHERE ta.teacher_id = ?";
    $params = [$teacherId];
    if ($yearId) { $sql .= " AND ta.academic_year_id = ?"; $params[] = $yearId; }
    $sql .= " ORDER BY ay.start_date DESC, g.level ASC, sec.name ASC";
    return Database::fetchAll($sql, $params);
}

/**
 * Teacher assigned subjects (unique) for a year.
 */
function teacher_assigned_subjects(int $teacherId, ?int $yearId = null): array
{
    $sql = "SELECT DISTINCT sub.id, sub.code, sub.name
            FROM teacher_assignments ta
            JOIN subjects sub ON sub.id = ta.subject_id
            WHERE ta.teacher_id = ?";
    $params = [$teacherId];
    if ($yearId) { $sql .= " AND ta.academic_year_id = ?"; $params[] = $yearId; }
    $sql .= " ORDER BY sub.name ASC";
    return Database::fetchAll($sql, $params);
}

/**
 * Number of assigned students for a teacher.
 */
function teacher_student_count(int $teacherId, ?int $yearId = null): int
{
    $sql = "SELECT COUNT(DISTINCT e.student_id)
            FROM teacher_assignments ta
            JOIN enrollments e
              ON e.section_id = ta.section_id
             AND e.academic_year_id = ta.academic_year_id
             AND e.status = 'active'
            WHERE ta.teacher_id = ?";
    $params = [$teacherId];
    if ($yearId) { $sql .= " AND ta.academic_year_id = ?"; $params[] = $yearId; }
    $stmt = Database::conn()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Today's attendance for teacher (per assigned section).
 */
function teacher_today_attendance(int $teacherId): array
{
    $today = date('Y-m-d');
    $row = Database::fetch(
        "SELECT COUNT(a.id) AS total,
                SUM(a.status='present') AS present,
                SUM(a.status='absent')  AS absent,
                SUM(a.status='late')    AS late,
                SUM(a.status='excused') AS excused
         FROM attendance a
         JOIN teacher_assignments ta
           ON ta.section_id = a.section_id
          AND ta.academic_year_id = a.academic_year_id
         WHERE ta.teacher_id = ? AND a.date = ?",
        [$teacherId, $today]
    ) ?: ['total'=>0,'present'=>0,'absent'=>0,'late'=>0,'excused'=>0];

    $tot = (int)$row['total'];
    $ok  = (int)$row['present'] + (int)$row['late'] + (int)$row['excused'];
    $row['percentage'] = $tot > 0 ? round(($ok / $tot) * 100, 1) : 0;
    return $row;
}

/**
 * Count of pending marks for teacher's assigned exam subjects.
 */
function teacher_pending_marks(int $teacherId): int
{
    $row = Database::fetch(
        "SELECT COUNT(*) AS cnt
         FROM examination_subjects es
         JOIN examinations ex ON ex.id = es.examination_id
         JOIN teacher_assignments ta
           ON ta.section_id = es.section_id
          AND ta.subject_id = es.subject_id
          AND ta.academic_year_id = ex.academic_year_id
         WHERE ta.teacher_id = ?
           AND ex.status IN ('open','completed')
           AND NOT EXISTS (
                SELECT 1 FROM marks m
                WHERE m.exam_subject_id = es.id AND m.score IS NOT NULL
           )",
        [$teacherId]
    ) ?: ['cnt'=>0];

    return (int)$row['cnt'];
}

/**
 * Recent students in teacher's classes (last 5 enrolled).
 */
function teacher_recent_students(int $teacherId, int $limit = 5): array
{
    return Database::fetchAll(
        "SELECT DISTINCT s.id, s.student_code, s.first_name, s.middle_name, s.last_name, s.photo
         FROM students s
         JOIN enrollments e ON e.student_id = s.id
         JOIN teacher_assignments ta
              ON ta.section_id = e.section_id
             AND ta.academic_year_id = e.academic_year_id
         WHERE ta.teacher_id = ? AND e.status = 'active'
         ORDER BY s.id DESC LIMIT ?",
        [$teacherId, $limit]
    );
}

/**
 * Latest announcement feed for a role/user.
 */
function announcement_feed(string $role, int $userId, ?int $gradeId = null, ?int $sectionId = null, int $limit = 5): array
{
    return visible_announcements($role, $userId, (int)$gradeId, (int)$sectionId, $limit);
}

/**
 * Render a compact announcement banner for dashboards.
 */
function render_announcement_banner(array $a): string
{
    $tone = match ($a['priority']) {
        'urgent' => 'urgent',
        'high'   => 'high',
        default  => '',
    };
    $icon = match ($a['priority']) {
        'urgent' => 'bi-exclamation-octagon-fill',
        'high'   => 'bi-exclamation-triangle-fill',
        default  => 'bi-megaphone-fill',
    };
    $href = url(role_home($_SESSION['user']['primary_role'] ?? 'student') === 'auth/login.php'
        ? 'auth/login.php'
        : str_replace('dashboard.php', 'announcements.php', role_home($_SESSION['user']['primary_role'] ?? 'student')));
    // More robust: link to role's announcements page
    $role = $_SESSION['user']['primary_role'] ?? 'student';
    $link = match ($role) {
        'admin'   => url('admin/announcements/show.php?id=' . (int)$a['id']),
        'teacher' => url('teacher/announcements/index.php'),
        'student' => url('student/announcements.php'),
        'parent'  => url('parent/announcements.php'),
        default   => url('index.php'),
    };
    return '<a href="' . e($link) . '" class="ann-banner ' . e($tone) . ' text-decoration-none">'
         . '<span class="icon"><i class="bi ' . $icon . '"></i></span>'
         . '<div class="flex-grow-1 min-w-0">'
         . '<div class="fw-semibold text-truncate">' . e($a['title']) . '</div>'
         . '<div class="text-muted small">' . e(fmt_date($a['publish_date'] ?? $a['created_at'], 'd M Y')) . ' · '
         . e(announcement_priority_badge_text($a['priority'])) . '</div>'
         . '</div>'
         . '<i class="bi bi-chevron-right text-muted"></i>'
         . '</a>';
}

/**
 * Priority text label (used inside banners).
 */
function announcement_priority_badge_text(string $priority): string
{
    return match ($priority) {
        'low'    => 'Low priority',
        'normal' => 'Normal',
        'high'   => 'High priority',
        'urgent' => 'URGENT',
        default  => ucfirst($priority),
    };
}