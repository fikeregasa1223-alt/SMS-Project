<?php
declare(strict_types=1);

if (defined('SMS_RATELIMIT_LOADED')) { return; }
define('SMS_RATELIMIT_LOADED', true);

/**
 * Rate limit keyed by an identifier (e.g. "login:1.2.3.4").
 * @return array{allowed:bool, retry_after:int}
 */
function rate_limit_check(string $key, int $maxAttempts = 10, int $windowSeconds = 300): array
{
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sms_rl_' . md5($key) . '.json';
    $now  = time();

    $data = ['count' => 0, 'first' => $now, 'until' => 0];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $parsed = $raw ? json_decode($raw, true) : null;
        if (is_array($parsed)) $data = array_merge($data, $parsed);
    }

    // Lock still active?
    if ($data['until'] > $now) {
        return ['allowed' => false, 'retry_after' => $data['until'] - $now];
    }

    // New window?
    if (($now - $data['first']) > $windowSeconds) {
        $data = ['count' => 0, 'first' => $now, 'until' => 0];
    }

    $data['count']++;

    if ($data['count'] > $maxAttempts) {
        $data['until'] = $now + $windowSeconds;
        @file_put_contents($file, json_encode($data), LOCK_EX);
        return ['allowed' => false, 'retry_after' => $windowSeconds];
    }

    @file_put_contents($file, json_encode($data), LOCK_EX);
    return ['allowed' => true, 'retry_after' => 0];
}

/**
 * Rate limit check that terminates with a JSON/HTML response if blocked.
 */
function rate_limit_or_429(string $key, int $maxAttempts = 10, int $windowSeconds = 300): void
{
    $check = rate_limit_check($key, $maxAttempts, $windowSeconds);
    if (!$check['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . (int)$check['retry_after']);
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Too Many Requests</title></head><body style="font-family:system-ui;text-align:center;padding:60px;"><h1 style="color:#ef4444;">Too Many Requests</h1><p>Please wait ' . (int)$check['retry_after'] . ' seconds before trying again.</p></body></html>';
        exit;
    }
}

/**
 * Clear rate limit for a key (e.g. on successful login).
 */
function rate_limit_clear(string $key): void
{
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sms_rl_' . md5($key) . '.json';
    if (is_file($file)) @unlink($file);
}