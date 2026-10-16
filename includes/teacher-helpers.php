<?php
declare(strict_types=1);

/**
 * SMS — Teacher helpers
 */

if (defined('SMS_TEACHER_HELPERS_LOADED')) { return; }
define('SMS_TEACHER_HELPERS_LOADED', true);

/**
 * Next teacher code: TC-0001, TC-0002, ...
 */
function next_teacher_code(): string
{
    $last = Database::fetch("SELECT teacher_code FROM teachers ORDER BY id DESC LIMIT 1");
    $n    = 1;
    if ($last && preg_match('/^TC-(\d+)$/', (string)$last['teacher_code'], $m)) {
        $n = (int)$m[1] + 1;
    }
    return 'TC-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

/**
 * Next employee number: EMP-YYYY-0001
 */
function next_employee_no(): string
{
    $year = date('Y');
    $last = Database::fetch(
        "SELECT employee_no FROM teachers WHERE employee_no LIKE ? ORDER BY id DESC LIMIT 1",
        ['EMP-' . $year . '-%']
    );
    $n = 1;
    if ($last && preg_match('/^EMP-' . $year . '-(\d+)$/', (string)$last['employee_no'], $m)) {
        $n = (int)$m[1] + 1;
    }
    return sprintf('EMP-%s-%04d', $year, $n);
}

/**
 * Full name from a teacher row.
 */
function teacher_full_name(array $t): string
{
    $parts = [$t['first_name'] ?? '', $t['middle_name'] ?? '', $t['last_name'] ?? ''];
    return trim(implode(' ', array_filter($parts, fn($p) => $p !== null && $p !== '')));
}

/**
 * Employment status badge.
 */
function employment_status_badge(string $status): string
{
    return match ($status) {
        'full_time'  => ui_badge('Full Time',  'success'),
        'part_time'  => ui_badge('Part Time',  'info'),
        'contract'   => ui_badge('Contract',   'warning'),
        'terminated' => ui_badge('Terminated', 'danger'),
        default      => ui_badge(ucfirst($status), 'secondary'),
    };
}

/**
 * Employment status list.
 */
function employment_status_list(): array
{
    return ['full_time', 'part_time', 'contract', 'terminated'];
}

/**
 * Photo URL for teacher row.
 */
function teacher_photo_url(?array $t): ?string
{
    if (!$t || empty($t['photo'])) return null;
    return url('uploads/profiles/' . $t['photo']);
}

/**
 * Count assigned students for a teacher (unique).
 */
function teacher_assigned_students(int $teacherId, ?int $yearId = null): int
{
    $sql = "SELECT COUNT(DISTINCT e.student_id)
            FROM teacher_assignments ta
            JOIN enrollments e
              ON e.section_id = ta.section_id
             AND e.academic_year_id = ta.academic_year_id
             AND e.status = 'active'
            WHERE ta.teacher_id = ?";
    $params = [$teacherId];
    if ($yearId) {
        $sql .= " AND ta.academic_year_id = ?";
        $params[] = $yearId;
    }
    $stmt = Database::conn()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}