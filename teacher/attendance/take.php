<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

sms_session_start();
require_login();
require_role('teacher');

// Redirect to admin attendance take page (teacher-scoped loading enforced at the data level)
redirect(url('admin/attendance/take.php'));