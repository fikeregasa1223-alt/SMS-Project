<?php
declare(strict_types=1);

/**
 * SMS — Grading helpers
 */

if (defined('SMS_GRADING_HELPERS_LOADED')) { return; }
define('SMS_GRADING_HELPERS_LOADED', true);

/**
 * Look up the grade letter + points for a given percentage using grading_rules.
 * Optionally restrict by grade_id (falls back to NULL rules = global).
 */
function resolve_grade(float $percentage, ?int $gradeId = null): array
{
    // Prefer grade-specific rules
    $sql = "SELECT letter, points, remark FROM grading_rules
            WHERE is_active = 1 AND ? BETWEEN min_score AND max_score
            AND (grade_id = ? OR grade_id IS NULL)
            ORDER BY grade_id IS NULL ASC
            LIMIT 1";
    $row = Database::fetch($sql, [$percentage, $gradeId]);
    if ($row) {
        return [
            'letter' => $row['letter'],
            'points' => (float)$row['points'],
            'remark' => $row['remark'] ?? null,
        ];
    }
    return ['letter' => null, 'points' => null, 'remark' => null];
}

/**
 * Color tone for a letter grade (for badges).
 */
function grade_tone(?string $letter): string
{
    if (!$letter) return 'secondary';
    $l = strtoupper($letter);
    return match (true) {
        str_starts_with($l, 'A') => 'success',
        str_starts_with($l, 'B') => 'primary',
        str_starts_with($l, 'C') => 'info',
        str_starts_with($l, 'D') => 'warning',
        str_starts_with($l, 'F') => 'danger',
        default                  => 'secondary',
    };
}

/**
 * Render a letter-grade badge.
 */
function grade_badge(?string $letter): string
{
    if (!$letter) return '<span class="text-muted">—</span>';
    $tone = grade_tone($letter);
    return '<span class="badge bg-' . $tone . '-subtle text-' . $tone . '-emphasis border border-' . $tone . '-subtle fw-bold">'
         . e(strtoupper($letter)) . '</span>';
}

/**
 * Compute total + percentage + letter + points from a raw score.
 */
function compute_mark(float $score, float $maxMark, ?int $gradeId = null): array
{
    $maxMark = $maxMark > 0 ? $maxMark : 100;
    $pct     = round(($score / $maxMark) * 100, 2);
    $g       = resolve_grade($pct, $gradeId);
    return [
        'percentage'   => $pct,
        'grade_letter' => $g['letter'],
        'grade_point'  => $g['points'],
        'remark'       => $g['remark'],
    ];
}

/**
 * Status badge for an examination.
 */
function exam_status_badge(string $status): string
{
    return match ($status) {
        'draft'     => '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Draft</span>',
        'open'      => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Open</span>',
        'completed' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Completed</span>',
        'published' => '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Published</span>',
        default     => '<span class="badge bg-light text-dark border">' . e($status) . '</span>',
    };
}

/**
 * Exam type list.
 */
function exam_type_list(): array
{
    return ['quiz', 'assignment', 'midterm', 'final', 'other'];
}

/**
 * Exam status list.
 */
function exam_status_list(): array
{
    return ['draft', 'open', 'completed', 'published'];
}