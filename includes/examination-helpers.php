<?php
declare(strict_types=1);

/**
 * SMS — Examination helpers
 */

if (defined('SMS_EXAM_HELPERS_LOADED')) { return; }
define('SMS_EXAM_HELPERS_LOADED', true);

/**
 * Human-friendly exam type label.
 */
function exam_type_label(string $type): string
{
    return match ($type) {
        'quiz'       => 'Quiz',
        'assignment' => 'Assignment',
        'midterm'    => 'Midterm',
        'final'      => 'Final Exam',
        'other'      => 'Other',
        default      => ucfirst($type),
    };
}

/**
 * Fetch exam subjects for an exam, with subject + section info.
 */
function exam_subjects(int $examinationId): array
{
    return Database::fetchAll(
        "SELECT es.*, sub.code AS subject_code, sub.name AS subject_name,
                sec.name AS section_name, g.name AS grade_name
         FROM examination_subjects es
         JOIN subjects sub ON sub.id = es.subject_id
         JOIN sections sec ON sec.id = es.section_id
         JOIN grades   g   ON g.id   = sub.grade_id
         WHERE es.examination_id = ?
         ORDER BY g.level ASC, sec.name ASC, sub.name ASC",
        [$examinationId]
    );
}

/**
 * Marks count for an exam subject.
 */
function marks_progress(int $examSubjectId): array
{
    $row = Database::fetch(
        "SELECT COUNT(*) AS total,
                SUM(score IS NOT NULL) AS entered
         FROM marks WHERE exam_subject_id = ?",
        [$examSubjectId]
    ) ?: ['total'=>0,'entered'=>0];
    $total   = (int)$row['total'];
    $entered = (int)$row['entered'];
    return [
        'total'    => $total,
        'entered'  => $entered,
        'missing'  => max(0, $total - $entered),
        'percent'  => $total > 0 ? round(($entered / $total) * 100, 1) : 0,
    ];
}

/**
 * Students enrolled in a section for a given year (for marks entry).
 */
function students_for_marks(int $examinationId, int $subjectId, int $sectionId, int $yearId): array
{
    return Database::fetchAll(
        "SELECT s.id, s.student_code, s.admission_no,
                s.first_name, s.middle_name, s.last_name, s.photo,
                e.id AS enrollment_id, e.roll_no,
                m.id AS mark_id, m.score, m.max_mark, m.percentage,
                m.grade_letter, m.grade_point, m.is_absent, m.remarks
         FROM enrollments e
         JOIN students s ON s.id = e.student_id
         LEFT JOIN marks m
                ON m.student_id = s.id
               AND m.examination_id = :exam
               AND m.subject_id     = :subj
         WHERE e.section_id = :sec AND e.academic_year_id = :year AND e.status = 'active'
         ORDER BY e.roll_no ASC, s.first_name ASC, s.last_name ASC",
        [
            'exam' => $examinationId,
            'subj' => $subjectId,
            'sec'  => $sectionId,
            'year' => $yearId,
        ]
    );
}

/**
 * Summary of exam results — pass/fail/average.
 */
function exam_summary(int $examinationId): array
{
    $row = Database::fetch(
        "SELECT COUNT(*) AS total,
                SUM(percentage >= 50) AS passed,
                SUM(percentage < 50)  AS failed,
                AVG(percentage)       AS avg_pct,
                MAX(percentage)       AS max_pct,
                MIN(percentage)       AS min_pct
         FROM marks
         WHERE examination_id = ? AND percentage IS NOT NULL",
        [$examinationId]
    ) ?: ['total'=>0,'passed'=>0,'failed'=>0,'avg_pct'=>0,'max_pct'=>0,'min_pct'=>0];

    return [
        'total'   => (int)$row['total'],
        'passed'  => (int)$row['passed'],
        'failed'  => (int)$row['failed'],
        'avg_pct' => round((float)$row['avg_pct'], 1),
        'max_pct' => round((float)$row['max_pct'], 1),
        'min_pct' => round((float)$row['min_pct'], 1),
        'pass_rate' => (int)$row['total'] > 0
            ? round(((int)$row['passed'] / (int)$row['total']) * 100, 1)
            : 0,
    ];
}

/**
 * Status chip for marks progress.
 */
function marks_progress_badge(array $p): string
{
    if ($p['total'] === 0) {
        return '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">No students</span>';
    }
    if ($p['missing'] === 0) {
        return '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="bi bi-check2-all me-1"></i>Complete</span>';
    }
    return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">'
         . $p['entered'] . ' / ' . $p['total'] . ' entered</span>';
}