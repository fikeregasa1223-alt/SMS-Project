<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_permission('edit_marks');

$id = (int) ($_GET['id'] ?? 0);
Database::conn()->prepare("DELETE FROM grading_rules WHERE id = ?")->execute([$id]);
audit((int)current_user()['id'], 'grading_rule_deleted', 'grading_rules', $id, 'Rule deleted');
flash_set('success', 'Grading rule deleted.');
redirect(url('admin/grading/index.php'));