<?php
declare(strict_types=1);

/**
 * SMS — Attendance helpers
 * Path: includes/attendance-helpers.php
 */

if (defined('SMS_ATTENDANCE_HELPERS_LOADED')) { return; }
define('SMS_ATTENDANCE_HELPERS_LOADED', true);

/**
 * Attendance status list.
 */
function attendance_status_list(): array
{
    return ['present', 'absent', 'late', 'excused'];
}

/**
 * Status badge with icon.
 */
function attendance_status_badge(string $status): string
{
    $map = [
        'present' => ['Present', 'success', 'bi-check-circle-fill'],
        'absent'  => ['Absent',  'danger',  'bi-x-circle-fill'],
        'late'    => ['Late',    'warning', 'bi-clock-fill'],
        'excused' => ['Excused', 'info',    'bi-shield-fill-check'],
    ];
    $m = $map[$status] ?? ['Unknown', 'secondary', 'bi-question-circle'];
    return '<span class="badge bg-' . $m[1] . '-subtle text-' . $m[1] . '-emphasis border border-' . $m[1] . '-subtle">'
         . '<i class="bi ' . $m[2] . ' me-1"></i>' . e($m[0]) . '</span>';
}

/**
 * Attendance percentage for a student.
 *
 * @return array{total:int, present:int, absent:int, late:int, excused:int, percentage:float}
 */
function attendance_percentage(int $studentId, ?int $yearId = null, ?int $termId = null): array
{
    $sql = "SELECT
              COUNT(*) AS total,
              SUM(status='present') AS present,
              SUM(status='absent')  AS absent,
              SUM(status='late')    AS late,
              SUM(status='excused') AS excused
            FROM attendance WHERE student_id = ?";
    $params = [$studentId];
    if ($yearId) { $sql .= " AND academic_year_id = ?"; $params[] = $yearId; }
    if ($termId) { $sql .= " AND term_id = ?";          $params[] = $termId; }

    $row = Database::fetch($sql, $params) ?: ['total'=>0,'present'=>0,'absent'=>0,'late'=>0,'excused'=>0];
    $total = (int)$row['total'];
    $pct   = $total > 0
        ? round((((int)$row['present'] + (int)$row['late'] + (int)$row['excused']) / $total) * 100, 1)
        : 0.0;

    return [
        'total'      => $total,
        'present'    => (int)$row['present'],
        'absent'     => (int)$row['absent'],
        'late'       => (int)$row['late'],
        'excused'    => (int)$row['excused'],
        'percentage' => (float)$pct,
    ];
}

/**
 * Current attendance count for a section on a date.
 *
 * @return array{present:int, absent:int, late:int, excused:int, total:int}
 */
function attendance_section_status(int $sectionId, string $date, ?int $subjectId = null): array
{
    $sql = "SELECT status, COUNT(*) AS cnt FROM attendance
            WHERE section_id = ? AND date = ?";
    $params = [$sectionId, $date];
    if ($subjectId) {
        $sql .= " AND subject_id = ?";
        $params[] = $subjectId;
    }
    $sql .= " GROUP BY status";

    $rows = Database::fetchAll($sql, $params);
    $out  = ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0,'total'=>0];
    foreach ($rows as $r) {
        $out[$r['status']] = (int)$r['cnt'];
        $out['total'] += (int)$r['cnt'];
    }
    return $out;
}

/**
 * Students in a section for a given academic year.
 */
function students_in_section(int $sectionId, int $academicYearId): array
{
    return Database::fetchAll(
        "SELECT s.id, s.student_code, s.admission_no,
                s.first_name, s.middle_name, s.last_name, s.gender, s.photo,
                e.id AS enrollment_id, e.roll_no
         FROM enrollments e
         JOIN students s ON s.id = e.student_id
         WHERE e.section_id = ? AND e.academic_year_id = ? AND e.status = 'active'
         ORDER BY e.roll_no ASC, s.first_name ASC, s.last_name ASC",
        [$sectionId, $academicYearId]
    );
}

/**
 * Render a small stat card for attendance summary.
 *
 * Accepts int OR float for $value — percentages arrive as float.
 * Displays decimals for non-integer values (e.g. 85.7), whole numbers otherwise.
 *
 * @param string      $label
 * @param int|float   $value
 * @param string      $icon   Bootstrap icon class (e.g. "bi-percent")
 * @param string      $tone   primary|success|warning|danger|info|secondary
 */
function attendance_stat_card(string $label, int|float $value, string $icon, string $tone = 'primary'): string
{
    // Preserve decimals for percentages, drop them for whole numbers
    $display = (is_float($value) && $value != (int)$value)
        ? number_format($value, 1)
        : number_format((int)$value);

    return '<div class="card stat-card">'
         . '<div class="stat-icon icon-' . e($tone) . '"><i class="bi ' . e($icon) . '"></i></div>'
         . '<div>'
         .   '<div class="stat-value">' . $display . '</div>'
         .   '<div class="stat-label">' . e($label) . '</div>'
         . '</div>'
         . '</div>';
}