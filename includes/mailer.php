<?php
declare(strict_types=1);

/**
 * SMS — Mailer
 * Uses PHPMailer if available (vendor/phpmailer/PHPMailer.php),
 * falls back to PHP mail().
 */

if (defined('SMS_MAILER_LOADED')) { return; }
define('SMS_MAILER_LOADED', true);

/**
 * Send an email.
 *
 * @return array{ok:bool, error:?string}
 */
function send_mail(string $to, string $subject, string $bodyHtml, ?string $replyTo = null): array
{
    $fromEmail = setting('school_email', 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $fromName  = setting('school_name',  'School Management System');

    // Try PHPMailer if present
    $phpMailerPath = APP_ROOT . '/vendor/phpmailer/src/PHPMailer.php';
    if (is_file($phpMailerPath)) {
        return send_mail_phpmailer($to, $subject, $bodyHtml, $fromEmail, $fromName, $replyTo);
    }

    // Fallback: PHP mail()
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    if ($replyTo) $headers .= "Reply-To: {$replyTo}\r\n";

    $ok = @mail($to, $subject, $bodyHtml, $headers);
    if (!$ok) {
        error_log("[SMS][mailer] mail() failed for {$to} subject={$subject}");
        return ['ok' => false, 'error' => 'mail() failed'];
    }
    return ['ok' => true, 'error' => null];
}

/**
 * Send via PHPMailer with SMTP.
 */
function send_mail_phpmailer(string $to, string $subject, string $bodyHtml, string $fromEmail, string $fromName, ?string $replyTo): array
{
    require_once APP_ROOT . '/vendor/phpmailer/src/Exception.php';
    require_once APP_ROOT . '/vendor/phpmailer/src/PHPMailer.php';
    require_once APP_ROOT . '/vendor/phpmailer/src/SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $mail->SMTPAuth   = defined('SMTP_USER') && SMTP_USER !== '';
        if ($mail->SMTPAuth) {
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
        }
        $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $mail->SMTPSecure = defined('SMTP_SECURE') && SMTP_SECURE === 'tls'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        if ($replyTo) $mail->addReplyTo($replyTo);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (\Throwable $e) {
        error_log('[SMS][mailer] PHPMailer error: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send a templated email using SMS styling.
 */
function send_templated_mail(string $to, string $subject, string $contentHtml): array
{
    $schoolName = setting('school_name', 'SMS');
    $logoUrl    = '';
    $logo       = setting('school_logo', '');
    if ($logo && file_exists(path('uploads/school/' . $logo))) {
        $logoUrl = url('uploads/school/' . $logo);
    }

    $body = '<!doctype html><html><head><meta charset="utf-8"><style>
        body { font-family: Inter, system-ui, sans-serif; background:#f5f6fa; margin:0; padding:24px; color:#0f172a; }
        .wrap { max-width:600px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.06); }
        .head { background: linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; padding:24px; text-align:center; }
        .head h1 { margin:0; font-size:1.25rem; }
        .body { padding:24px; line-height:1.6; color:#334155; }
        .foot { padding:16px; text-align:center; color:#94a3b8; font-size:.75rem; background:#f8fafc; }
        a { color:#4f46e5; }
    </style></head><body>
    <div class="wrap">
        <div class="head">'
        . ($logoUrl ? '<img src="' . e($logoUrl) . '" style="max-height:50px;margin-bottom:12px;">' : '')
        . '<h1>' . e($schoolName) . '</h1></div>
        <div class="body">' . $contentHtml . '</div>
        <div class="foot">' . e(setting('school_name', 'SMS')) . ' · This is an automated message.</div>
    </div>
    </body></html>';

    return send_mail($to, $subject, $body);
}