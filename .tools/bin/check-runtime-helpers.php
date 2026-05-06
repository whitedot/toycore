#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
define('TOY_ROOT', $root);

require_once $root . '/core/helpers/runtime.php';

$errors = [];

function toy_runtime_helper_error(string $message): void
{
    global $errors;
    $errors[] = $message;
}

function toy_runtime_helper_assert(bool $condition, string $message): void
{
    if (!$condition) {
        toy_runtime_helper_error($message);
    }
}

function toy_runtime_helper_server(array $server): void
{
    $_SERVER = $server;
}

$proxyConfig = [
    'security' => [
        'trusted_proxies' => [
            '10.0.0.0/8',
            '10.0.0.0/8',
            '203.0.113.10',
            '2001:db8::/32',
            '',
            'not-an-ip',
        ],
    ],
];

toy_runtime_helper_assert(
    toy_trusted_proxy_entries($proxyConfig) === ['10.0.0.0/8', '203.0.113.10', '2001:db8::/32'],
    'Trusted proxy entries should keep valid unique IP/CIDR values.'
);
toy_runtime_helper_assert(
    count(toy_trusted_proxy_config_errors($proxyConfig)) === 2,
    'Trusted proxy config errors should count invalid entries.'
);
toy_runtime_helper_assert(
    toy_ip_matches_trusted_proxy('10.2.3.4', '10.0.0.0/8'),
    'IPv4 CIDR trusted proxy match failed.'
);
toy_runtime_helper_assert(
    !toy_ip_matches_trusted_proxy('11.2.3.4', '10.0.0.0/8'),
    'IPv4 CIDR trusted proxy mismatch failed.'
);
toy_runtime_helper_assert(
    toy_ip_matches_trusted_proxy('2001:db8::1234', '2001:db8::/32'),
    'IPv6 CIDR trusted proxy match failed.'
);
toy_runtime_helper_assert(
    toy_ip_matches_trusted_proxy('203.0.113.10', '203.0.113.10'),
    'Exact trusted proxy match failed.'
);

toy_runtime_helper_server([
    'REMOTE_ADDR' => '10.0.0.10',
    'HTTP_X_FORWARDED_PROTO' => 'https',
]);
toy_runtime_helper_assert(
    toy_is_https_request($proxyConfig),
    'Trusted X-Forwarded-Proto=https should be treated as HTTPS.'
);

toy_runtime_helper_server([
    'REMOTE_ADDR' => '198.51.100.1',
    'HTTP_X_FORWARDED_PROTO' => 'https',
]);
toy_runtime_helper_assert(
    !toy_is_https_request($proxyConfig),
    'Untrusted X-Forwarded-Proto should not be treated as HTTPS.'
);

toy_runtime_helper_server([
    'REMOTE_ADDR' => '10.0.0.10',
    'HTTP_X_FORWARDED_FOR' => '198.51.100.25, 10.0.0.8',
]);
toy_set_runtime_config($proxyConfig);
toy_runtime_helper_assert(
    toy_forwarded_client_ip($proxyConfig) === '198.51.100.25',
    'Forwarded client IP should select the nearest untrusted address.'
);
toy_runtime_helper_assert(
    toy_client_ip() === '198.51.100.25',
    'Client IP should use trusted forwarded address.'
);

toy_runtime_helper_assert(
    toy_session_cookie_secure(['security' => ['force_https' => true]]) === true,
    'force_https should force Secure session cookies.'
);

putenv('TOY_TEST_APP_KEY=env-secret');
toy_runtime_helper_assert(
    toy_app_key(['app_key' => 'file-secret', 'secrets' => ['app_key_env' => 'TOY_TEST_APP_KEY']]) === 'env-secret',
    'App key environment override failed.'
);
putenv('TOY_TEST_APP_KEY');
toy_runtime_helper_assert(
    toy_app_key(['app_key' => 'file-secret', 'secrets' => ['app_key_env' => 'TOY_TEST_APP_KEY']]) === 'file-secret',
    'App key file fallback failed.'
);

if ($errors !== []) {
    fwrite(STDERR, "runtime helper checks failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

echo "runtime helper checks completed.\n";
