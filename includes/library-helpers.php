<?php
declare(strict_types=1);

if (defined('SMS_LIB_HELPERS_LOADED')) { return; }
define('SMS_LIB_HELPERS_LOADED', true);

const LIBRARY_DEFAULT_LOAN_DAYS = 14;
const LIBRARY_FINE_PER_DAY      = 5.00;

/**
 * Compute fine for overdue issue.
 */
function library_fine(array $issue): float
{
    if ($issue['status'] === 'returned') return (float)$issue['fine_amount'];
    $due = strtotime($issue['due_date']);
    $now = time();
    if ($now <= $due) return 0.0;
    $days = (int)ceil(($now - $due) / 86400);
    return round($days * LIBRARY_FINE_PER_DAY, 2);
}

/**
 * Issue status badge.
 */
function library_issue_status_badge(string $status): string
{
    return match ($status) {
        'issued'   => ui_badge('Issued',   'info'),
        'returned' => ui_badge('Returned', 'success'),
        'overdue'  => ui_badge('Overdue',  'danger'),
        'lost'     => ui_badge('Lost',     'warning'),
        default    => ui_badge(ucfirst($status), 'secondary'),
    };
}

/**
 * Decrement available copies after an issue.
 */
function library_mark_issued(int $bookId): void
{
    Database::conn()->prepare(
        "UPDATE library_books SET available_copies = GREATEST(available_copies - 1, 0) WHERE id = ?"
    )->execute([$bookId]);
}

/**
 * Increment available copies after a return.
 */
function library_mark_returned(int $bookId): void
{
    Database::conn()->prepare(
        "UPDATE library_books SET available_copies = LEAST(available_copies + 1, total_copies) WHERE id = ?"
    )->execute([$bookId]);
}