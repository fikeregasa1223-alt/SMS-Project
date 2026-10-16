<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('manage_attendance');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/attendance/take.php'));
}

csrf_check_or_die();

$yearId    = (int) post('academic_year_id', 0);
$termId    = (int) post('term_id', 0);
$gradeId   = (int) post('grade_id', 0);
$sectionId = (int) post('section_id', 0);
$subjectId = post('subject_id', '') !== '' ? (int) post('subject_id') : null;
$date      = clean((string) post('date', ''));
$statusMap = (array) post('status', []);

if ($yearId <= 0 || $termId <= 0 || $sectionId <= 0 || !$date) {
    flash_set('error', 'Missing required attendance parameters.');
    redirect(url('admin/attendance/take.php'));
}

if (!in_array($date, [date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))], true) && SMS_ENV === 'production') {
    // Optional guard: prevent backdating more than a day in production
}

if (!$statusMap) {
    flash_set('warning', 'No attendance data submitted.');
    redirect(url('admin/attendance/take.php?academic_year_id=' . $yearId
              . '&term_id=' . $termId . '&grade_id=' . $gradeId
              . '&section_id=' . $sectionId . '&date=' . $date));
}

$allowed = attendance_status_list();
$userId  = (int) current_user()['id'];

Database::begin();
try {
    $inserted = 0;
    $updated  = 0;

    foreach ($statusMap as $studentId => $status) {
        $studentId = (int)$studentId;
        if (!in_array($status, $allowed, true)) continue;

        // Look up active enrollment
        $enr = Database::fetch(
            "SELECT id FROM enrollments
             WHERE student_id = ? AND academic_year_id = ? AND section_id = ? AND status='active'
             LIMIT 1",
            [$studentId, $yearId, $sectionId]
        );
        if (!$enr) continue; // student not actively enrolled in this class
        $enrollmentId = (int)$enr['id'];

        // Check existing record (unique constraint: student + date + subject + term)
        $existingSql = "SELECT id FROM attendance
                        WHERE student_id = ? AND date = ? AND term_id = ?";
        $existingParams = [$studentId, $date, $termId];
        if ($subjectId !== null) {
            $existingSql .= " AND subject_id = ?";
            $existingParams[] = $subjectId;
        } else {
            $existingSql .= " AND subject_id IS NULL";
        }

        $existing = Database::fetch($existingSql, $existingParams);

        if ($existing) {
            Database::update('attendance', [
                'status'      => $status,
                'recorded_by' => $userId,
            ], 'id = :id', ['id' => (int)$existing['id']]);
            $updated++;
        } else {
            Database::insert('attendance', [
                'student_id'       => $studentId,
                'enrollment_id'    => $enrollmentId,
                'section_id'       => $sectionId,
                'subject_id'       => $subjectId,
                'term_id'          => $termId,
                'academic_year_id' => $yearId,
                'date'             => $date,
                'status'           => $status,
                'recorded_by'      => $userId,
            ]);
            $inserted++;
        }
    }

    audit($userId, 'attendance_saved', 'attendance', null,
          "Attendance saved for section #$sectionId on $date (ins:$inserted upd:$updated)");

    Database::commit();
    flash_set('success', "Attendance saved. $inserted new, $updated updated.");
} catch (Throwable $e) {
    Database::rollback();
    flash_set('error', 'Failed to save attendance. ' . (SMS_ENV === 'development' ? $e->getMessage() : ''));
}

redirect(url('admin/attendance/take.php?academic_year_id=' . $yearId
          . '&term_id=' . $termId . '&grade_id=' . $gradeId
          . '&section_id=' . $sectionId . '&date=' . $date));