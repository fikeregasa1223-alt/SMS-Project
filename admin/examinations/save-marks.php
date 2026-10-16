<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('enter_marks');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/examinations/index.php'));
}

csrf_check_or_die();

$examId        = (int) post('examination_id', 0);
$examSubjectId = (int) post('exam_subject_id', 0);
$subjectId     = (int) post('subject_id', 0);
$sectionId     = (int) post('section_id', 0);
$yearId        = (int) post('academic_year_id', 0);
$maxMark       = (float) post('max_mark', 100);
$scores        = (array) post('score', []);
$absents       = (array) post('absent', []);
$remarks       = (array) post('remarks', []);

if ($examId <= 0 || $examSubjectId <= 0 || $subjectId <= 0 || $sectionId <= 0) {
    flash_set('error', 'Missing context for saving marks.');
    redirect(url('admin/examinations/index.php'));
}

$exam = Database::fetch("SELECT grade_id FROM examinations WHERE id = ?", [$examId]);
$gradeId = (int)($exam['grade_id'] ?? 0);

$userId = (int) current_user()['id'];

Database::begin();
try {
    $saved = 0;

    foreach ($scores as $studentId => $rawScore) {
        $studentId = (int)$studentId;
        $isAbsent  = !empty($absents[$studentId]);
        $remark    = trim((string)($remarks[$studentId] ?? ''));
        $remark    = $remark !== '' ? mb_substr($remark, 0, 255) : null;

        // Get active enrollment
        $enr = Database::fetch(
            "SELECT id FROM enrollments
             WHERE student_id = ? AND academic_year_id = ? AND section_id = ? AND status='active' LIMIT 1",
            [$studentId, $yearId, $sectionId]
        );
        if (!$enr) continue;
        $enrollmentId = (int)$enr['id'];

        if ($isAbsent || $rawScore === '' || $rawScore === null) {
            // Absent or blank → null score
            $score = null;
            $percentage = null;
            $letter = null;
            $points = null;
        } else {
            $score = (float)$rawScore;
            if ($score < 0) $score = 0;
            if ($score > $maxMark) $score = $maxMark;

            $computed   = compute_mark($score, $maxMark, $gradeId);
            $percentage = $computed['percentage'];
            $letter     = $computed['grade_letter'];
            $points     = $computed['grade_point'];
        }

        // Upsert by unique key (examination_id, subject_id, student_id)
        $existing = Database::fetch(
            "SELECT id FROM marks WHERE examination_id = ? AND subject_id = ? AND student_id = ?",
            [$examId, $subjectId, $studentId]
        );

        $payload = [
            'score'          => $score,
            'max_mark'       => $maxMark,
            'percentage'     => $percentage,
            'grade_letter'   => $letter,
            'grade_point'    => $points,
            'is_absent'      => $isAbsent ? 1 : 0,
            'remarks'        => $remark,
            'entered_by'     => $userId,
        ];

        if ($existing) {
            Database::update('marks', $payload, 'id = :id', ['id' => (int)$existing['id']]);
        } else {
            $payload['examination_id']  = $examId;
            $payload['exam_subject_id'] = $examSubjectId;
            $payload['student_id']      = $studentId;
            $payload['enrollment_id']   = $enrollmentId;
            $payload['subject_id']      = $subjectId;
            $payload['section_id']      = $sectionId;
            Database::insert('marks', $payload);
        }
        $saved++;
    }

    audit($userId, 'marks_saved', 'marks', null,
          "Marks saved for exam #$examId subject #$subjectId section #$sectionId ($saved records)");

    Database::commit();
    flash_set('success', "Marks saved. $saved records updated.");
} catch (Throwable $e) {
    Database::rollback();
    flash_set('error', 'Failed to save marks. ' . (SMS_ENV === 'development' ? $e->getMessage() : ''));
}

redirect(url('admin/examinations/marks.php?exam_id=' . $examId . '&subject_id=' . $subjectId . '&section_id=' . $sectionId));