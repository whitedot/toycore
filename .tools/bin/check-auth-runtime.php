#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];

function toy_auth_runtime_error(string $message): void
{
    global $errors;
    $errors[] = $message;
}

function toy_auth_runtime_read(string $path): string
{
    global $root;

    $fullPath = $root . '/' . $path;
    $content = is_file($fullPath) ? file_get_contents($fullPath) : false;
    if (!is_string($content)) {
        toy_auth_runtime_error('Cannot read file: ' . $path);
        return '';
    }

    return $content;
}

function toy_auth_runtime_require(string $path, string $pattern, string $message): void
{
    $content = toy_auth_runtime_read($path);
    if ($content === '') {
        return;
    }

    if (preg_match($pattern, $content) !== 1) {
        toy_auth_runtime_error($message . ': ' . $path);
    }
}

foreach ([
    'database/core/install.sql',
    'database/core/updates/2026.04.006.sql',
] as $path) {
    toy_auth_runtime_require($path, '/CREATE TABLE IF NOT EXISTS toy_sessions\b/', 'Runtime session table is missing');
    toy_auth_runtime_require($path, '/CREATE TABLE IF NOT EXISTS toy_rate_limits\b/', 'Rate limit table is missing');
    toy_auth_runtime_require($path, '/UNIQUE KEY uq_toy_sessions_session_id/', 'Runtime session unique key is missing');
    toy_auth_runtime_require($path, '/UNIQUE KEY uq_toy_rate_limits_key/', 'Rate limit unique key is missing');
}

toy_auth_runtime_require('core/actions/install.php', "/'secrets'\\s*=>\\s*\\[/", 'Install config secrets block is missing');
toy_auth_runtime_require('core/actions/install.php', "/'app_key_env'\\s*=>\\s*'TOY_APP_KEY'/", 'Install config app key env is missing');
toy_auth_runtime_require('core/actions/install.php', "/'security'\\s*=>\\s*\\[/", 'Install config security block is missing');
toy_auth_runtime_require('core/actions/install.php', "/'trusted_proxies'\\s*=>\\s*\\[\\]/", 'Install config trusted proxies default is missing');
toy_auth_runtime_require('core/actions/install.php', "/'session'\\s*=>\\s*\\[/", 'Install config session block is missing');
toy_auth_runtime_require('core/actions/install.php', "/'handler'\\s*=>\\s*'database'/", 'Install config database session handler is missing');
toy_auth_runtime_require('core/actions/install.php', "/'mail'\\s*=>\\s*\\[/", 'Install config mail block is missing');

toy_auth_runtime_require('core/helpers/runtime.php', '/class ToyDatabaseSessionHandler implements SessionHandlerInterface/', 'Database session handler is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_is_https_request\(\?array \$config = null\): bool/', 'Proxy-aware HTTPS helper signature is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/HTTP_X_FORWARDED_PROTO/', 'Forwarded proto handling is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_forwarded_client_ip\(\?array \$config = null\): string/', 'Forwarded client IP helper is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_trusted_proxy_entries\(array \$config\): array/', 'Trusted proxy entry helper is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_trusted_proxy_config_errors\(array \$config\): array/', 'Trusted proxy config validation is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_send_smtp_mail\(/', 'SMTP mail transport helper is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_send_http_api_mail\(/', 'HTTP API mail transport helper is missing');
toy_auth_runtime_require('core/helpers/runtime.php', "/'from_email'\\s*=>\\s*\\\$fromEmail/", 'HTTP API mail payload from email is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_rate_limit_count\(/', 'Rate limit count helper is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_rate_limit_increment\(/', 'Rate limit increment helper is missing');
toy_auth_runtime_require('core/helpers/runtime.php', '/function toy_app_key\(array \$config\): string/', 'App key resolver is missing');

toy_auth_runtime_require('modules/member/helpers/throttle.php', '/toy_rate_limit_count\(/', 'Member throttle does not use rate limit counters');
toy_auth_runtime_require('modules/member/helpers/throttle.php', '/toy_member_auth_log_count\(/', 'Member throttle fallback is missing');
toy_auth_runtime_require('modules/member/helpers/accounts.php', '/toy_member_record_auth_rate_limits\(/', 'Auth log rate limit recording hook is missing');

toy_auth_runtime_require('modules/admin/helpers/retention.php', "/'runtime_sessions'\\s*=>\\s*\\[/", 'Runtime sessions retention target is missing');
toy_auth_runtime_require('modules/admin/helpers/retention.php', "/'rate_limits'\\s*=>\\s*\\[/", 'Rate limits retention target is missing');
toy_auth_runtime_require('modules/admin/helpers/dashboard.php', '/function toy_admin_dashboard_auth_runtime_summary\(/', 'Auth runtime dashboard summary is missing');
toy_auth_runtime_require('modules/admin/helpers/dashboard.php', '/toy_trusted_proxy_config_errors/', 'Dashboard trusted proxy validation is missing');
toy_auth_runtime_require('modules/admin/helpers/dashboard.php', '/toy_admin_dashboard_mail_transport_ready/', 'Mail transport dashboard validation is missing');
toy_auth_runtime_require('modules/admin/views/dashboard.php', '/인증 런타임/', 'Auth runtime dashboard view is missing');
toy_auth_runtime_require('docs/deployment-examples.md', '/로드밸런서와 클라우드 런타임/', 'Cloud runtime deployment documentation is missing');
toy_auth_runtime_require('docs/deployment-examples.md', '/http_api/', 'HTTP API mail documentation is missing');

if ($errors !== []) {
    fwrite(STDERR, "auth runtime checks failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

echo "auth runtime checks completed.\n";
