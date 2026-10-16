<?php
declare(strict_types=1);

/**
 * SMS — Announcement helpers
 */

if (defined('SMS_ANNOUNCEMENT_HELPERS_LOADED')) { return; }
define('SMS_ANNOUNCEMENT_HELPERS_LOADED', true);

/**
 * Priority list.
 */
function announcement_priority_list(): array
{
    return ['low', 'normal', 'high', 'urgent'];
}

/**
 * Audience list.
 */
function announcement_audience_list(): array
{
    return ['everyone', 'teachers', 'students', 'parents', 'grade', 'section'];
}

/**
 * Status list.
 */
function announcement_status_list(): array
{
    return ['draft', 'published', 'expired'];
}

/**
 * Priority badge.
 */
function announcement_priority_badge(string $priority): string
{
    return match ($priority) {
        'low'    => '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Low</span>',
        'normal' => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Normal</span>',
        'high'   => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">High</span>',
        'urgent' => '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">Urgent</span>',
        default  => '<span class="badge bg-light text-dark border">' . e($priority) . '</span>',
    };
}

/**
 * Status badge.
 */
function announcement_status_badge(string $status): string
{
    return match ($status) {
        'draft'     => '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Draft</span>',
        'published' => '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Published</span>',
        'expired'   => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Expired</span>',
        default     => '<span class="badge bg-light text-dark border">' . e($status) . '</span>',
    };
}

/**
 * Audience label.
 */
function announcement_audience_label(string $audience): string
{
    return match ($audience) {
        'everyone' => 'Everyone',
        'teachers' => 'Teachers',
        'students' => 'Students',
        'parents'  => 'Parents',
        'grade'    => 'Specific Grade',
        'section'  => 'Specific Section',
        default    => ucfirst($audience),
    };
}

/**
 * Audience badge.
 */
function announcement_audience_badge(string $audience): string
{
    $tone = match ($audience) {
        'everyone' => 'primary',
        'teachers' => 'success',
        'students' => 'info',
        'parents'  => 'warning',
        'grade'    => 'secondary',
        'section'  => 'secondary',
        default    => 'light',
    };
    return '<span class="badge bg-' . $tone . '-subtle text-' . $tone . '-emphasis border border-' . $tone . '-subtle">'
         . e(announcement_audience_label($audience)) . '</span>';
}

/**
 * Auto-expire announcements past expiry_date. Call this on lists/dashboards.
 */
function expire_old_announcements(): int
{
    $stmt = Database::conn()->prepare(
        "UPDATE announcements SET status = 'expired'
         WHERE status = 'published' AND expiry_date IS NOT NULL AND expiry_date < NOW()"
    );
    $stmt->execute();
    return $stmt->rowCount();
}

/**
 * Fetch announcements visible to a given role / user.
 *
 * @param string $role        admin|teacher|student|parent
 * @param int    $userId
 * @param int    $gradeId     (optional) for grade-scoped targeting
 * @param int    $sectionId   (optional) for section-scoped targeting
 * @param int    $limit
 * @return array
 */
function visible_announcements(string $role, int $userId = 0, int $gradeId = 0, int $sectionId = 0, int $limit = 20): array
{
    expire_old_announcements();

    $roleMap = [
        'teacher' => 'teachers',
        'student' => 'students',
        'parent'  => 'parents',
    ];
    $roleAudience = $roleMap[$role] ?? null;

    // Admins see everything
    if ($role === 'admin') {
        return Database::fetchAll(
            "SELECT a.*, u.full_name AS author
             FROM announcements a
             LEFT JOIN users u ON u.id = a.created_by
             WHERE a.status = 'published'
             ORDER BY FIELD(a.priority,'urgent','high','normal','low'), a.publish_date DESC
             LIMIT $limit"
        );
    }

    // Build conditions for everyone / role / grade / section
    $sql = "SELECT a.*, u.full_name AS author,
                   (ar.read_at IS NOT NULL) AS is_read
            FROM announcements a
            LEFT JOIN users u ON u.id = a.created_by
            LEFT JOIN announcement_recipients ar
                   ON ar.announcement_id = a.id AND ar.user_id = ?
            WHERE a.status = 'published'
              AND a.publish_date <= NOW()
              AND (a.expiry_date IS NULL OR a.expiry_date >= NOW())
              AND (
                    a.audience = 'everyone'";

    $params = [$userId];

    if ($roleAudience) {
        $sql .= " OR a.audience = ?";
        $params[] = $roleAudience;
    }
    if ($gradeId > 0) {
        $sql .= " OR (a.audience = 'grade'   AND a.grade_id = ?)";
        $params[] = $gradeId;
    }
    if ($sectionId > 0) {
        $sql .= " OR (a.audience = 'section' AND a.section_id = ?)";
        $params[] = $sectionId;
    }

    $sql .= ") ORDER BY FIELD(a.priority,'urgent','high','normal','low'), a.publish_date DESC LIMIT $limit";

    return Database::fetchAll($sql, $params);
}

/**
 * Count unread announcements for a user.
 */
function unread_announcement_count(string $role, int $userId, int $gradeId = 0, int $sectionId = 0): int
{
    $list = visible_announcements($role, $userId, $gradeId, $sectionId, 100);
    $count = 0;
    foreach ($list as $a) {
        if (empty($a['is_read'])) $count++;
    }
    return $count;
}

/**
 * Mark all visible announcements as read for a user.
 */
function mark_announcements_read(int $userId, array $announcementIds): void
{
    if (!$announcementIds) return;

    $in = implode(',', array_fill(0, count($announcementIds), '?'));

    // Get existing recipient rows
    $existing = Database::fetchAll(
        "SELECT announcement_id FROM announcement_recipients
         WHERE user_id = ? AND announcement_id IN ($in)",
        array_merge([$userId], $announcementIds)
    );
    $haveIds = array_map(fn($r) => (int)$r['announcement_id'], $existing);

    $now = date('Y-m-d H:i:s');

    foreach ($announcementIds as $aid) {
        $aid = (int)$aid;
        if (in_array($aid, $haveIds, true)) {
            Database::conn()->prepare(
                "UPDATE announcement_recipients SET read_at = ?
                 WHERE user_id = ? AND announcement_id = ? AND read_at IS NULL"
            )->execute([$now, $userId, $aid]);
        } else {
            Database::insert('announcement_recipients', [
                'announcement_id' => $aid,
                'user_id'         => $userId,
                'read_at'         => $now,
            ]);
        }
    }
}