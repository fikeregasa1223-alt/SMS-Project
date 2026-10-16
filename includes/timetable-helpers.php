<?php
declare(strict_types=1);

if (defined('SMS_TT_HELPERS_LOADED')) { return; }
define('SMS_TT_HELPERS_LOADED', true);

function timetable_days(): array
{
    return [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
}

function timetable_day_short(int $d): string
{
    return ['', 'Mon','Tue','Wed','Thu','Fri','Sat','Sun'][$d] ?? '—';
}

/**
 * Load a full section timetable as a grid: [day][period_id] => entry
 */
function timetable_grid(int $yearId, int $termId, int $sectionId): array
{
    $entries = Database::fetchAll(
        "SELECT te.*, sub.name AS subject_name, sub.code AS subject_code,
                t.first_name AS teacher_first, t.last_name AS teacher_last,
                p.name AS period_name, p.start_time, p.end_time, p.sequence, p.is_break
         FROM timetable_entries te
         JOIN timetable_periods p ON p.id = te.period_id
         JOIN subjects sub ON sub.id = te.subject_id
         LEFT JOIN teachers t ON t.id = te.teacher_id
         WHERE te.academic_year_id = ? AND te.term_id = ? AND te.section_id = ?
         ORDER BY p.sequence ASC",
        [$yearId, $termId, $sectionId]
    );

    $grid = [];
    foreach ($entries as $e) {
        $grid[(int)$e['day_of_week']][(int)$e['period_id']] = $e;
    }
    return $grid;
}

/**
 * Load periods for a year.
 */
function timetable_periods(int $yearId): array
{
    return Database::fetchAll(
        "SELECT * FROM timetable_periods WHERE academic_year_id = ? ORDER BY sequence ASC",
        [$yearId]
    );
}

/**
 * Check teacher conflict at a slot.
 */
function timetable_teacher_conflict(int $teacherId, int $yearId, int $termId, int $periodId, int $dayOfWeek, ?int $excludeId = null): ?array
{
    $sql = "SELECT * FROM timetable_entries
            WHERE teacher_id = ? AND academic_year_id = ? AND term_id = ?
              AND period_id = ? AND day_of_week = ?";
    $params = [$teacherId, $yearId, $termId, $periodId, $dayOfWeek];
    if ($excludeId) { $sql .= " AND id <> ?"; $params[] = $excludeId; }
    $sql .= " LIMIT 1";

    return Database::fetch($sql, $params) ?: null;
}

/**
 * Check subject-teacher availability.
 */
function timetable_section_conflict(int $sectionId, int $yearId, int $termId, int $periodId, int $dayOfWeek, ?int $excludeId = null): ?array
{
    $sql = "SELECT * FROM timetable_entries
            WHERE section_id = ? AND academic_year_id = ? AND term_id = ?
              AND period_id = ? AND day_of_week = ?";
    $params = [$sectionId, $yearId, $termId, $periodId, $dayOfWeek];
    if ($excludeId) { $sql .= " AND id <> ?"; $params[] = $excludeId; }
    $sql .= " LIMIT 1";

    return Database::fetch($sql, $params) ?: null;
}