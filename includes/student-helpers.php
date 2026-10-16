<?php
declare(strict_types=1);

/**
 * SMS — Student helpers
 */

if (defined('SMS_STUDENT_HELPERS_LOADED')) { return; }
define('SMS_STUDENT_HELPERS_LOADED', true);

/**
 * Generate the next student code: ST-0001, ST-0002, ...
 */
function next_student_code(): string
{
    $last = Database::fetch("SELECT student_code FROM students ORDER BY id DESC LIMIT 1");
    $n    = 1;
    if ($last && preg_match('/^ST-(\d+)$/', (string)$last['student_code'], $m)) {
        $n = (int)$m[1] + 1;
    }
    return 'ST-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate the next admission number: ADM-YYYY-0001
 */
function next_admission_no(): string
{
    $year = date('Y');
    $last = Database::fetch(
        "SELECT admission_no FROM students WHERE admission_no LIKE ? ORDER BY id DESC LIMIT 1",
        ['ADM-' . $year . '-%']
    );
    $n = 1;
    if ($last && preg_match('/^ADM-' . $year . '-(\d+)$/', (string)$last['admission_no'], $m)) {
        $n = (int)$m[1] + 1;
    }
    return sprintf('ADM-%s-%04d', $year, $n);
}

/**
 * Generate the next guardian code: GD-0001
 */
function next_guardian_code(): string
{
    $last = Database::fetch("SELECT parent_code FROM parents ORDER BY id DESC LIMIT 1");
    $n    = 1;
    if ($last && preg_match('/^GD-(\d+)$/', (string)$last['parent_code'], $m)) {
        $n = (int)$m[1] + 1;
    }
    return 'GD-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}

/**
 * Full name of a student row.
 */
function student_full_name(array $s): string
{
    $parts = [$s['first_name'] ?? '', $s['middle_name'] ?? '', $s['last_name'] ?? ''];
    return trim(implode(' ', array_filter($parts, fn($p) => $p !== null && $p !== '')));
}

/**
 * Student status badge.
 */
function student_status_badge(string $status): string
{
    return match ($status) {
        'active'      => ui_badge('Active', 'success'),
        'graduated'   => ui_badge('Graduated', 'primary'),
        'transferred' => ui_badge('Transferred', 'info'),
        'withdrawn'   => ui_badge('Withdrawn', 'warning'),
        'suspended'   => ui_badge('Suspended', 'danger'),
        default       => ui_badge(ucfirst($status), 'secondary'),
    };
}

/**
 * Status list for dropdowns.
 */
function student_status_list(): array
{
    return ['active', 'graduated', 'transferred', 'withdrawn', 'suspended'];
}

/**
 * Gender list for dropdowns.
 */
function gender_list(): array
{
    return ['male', 'female', 'other'];
}

/**
 * Guardian relationship list.
 */
function guardian_relationship_list(): array
{
    return ['father', 'mother', 'uncle', 'aunt', 'brother', 'sister', 'grandparent', 'legal_guardian', 'other'];
}

/**
 * Photo URL for a student row (or null if none).
 */
function student_photo_url(?array $s): ?string
{
    if (!$s || empty($s['photo'])) return null;
    return url('uploads/profiles/' . $s['photo']);
}

/**
 * Current enrollment row for a student.
 */
function student_current_enrollment(int $studentId): ?array
{
    return Database::fetch(
        "SELECT e.*, g.name AS grade_name, sec.name AS section_name, ay.name AS year_name
         FROM enrollments e
         JOIN grades   g   ON g.id   = e.grade_id
         JOIN sections sec ON sec.id = e.section_id
         JOIN academic_years ay ON ay.id = e.academic_year_id
         WHERE e.student_id = ? AND e.status = 'active'
         ORDER BY e.id DESC LIMIT 1",
        [$studentId]
    );
}