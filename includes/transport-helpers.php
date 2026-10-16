<?php
declare(strict_types=1);
if (defined('SMS_TRANSPORT_HELPERS_LOADED')) { return; }
define('SMS_TRANSPORT_HELPERS_LOADED', true);

function transport_route_badge(string $status): string
{
    return $status === 'active' ? ui_badge('Active', 'success') : ui_badge('Inactive', 'secondary');
}