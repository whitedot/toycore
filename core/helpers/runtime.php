<?php

declare(strict_types=1);

function toy_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_name('toy_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']),
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function toy_send_security_headers(?array $config = null): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    if (toy_is_https_request() && (empty($config) || (string) ($config['env'] ?? 'production') === 'production')) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function toy_is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function toy_current_base_url(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '' || preg_match('/[\x00-\x1F\x7F]/', $host) === 1) {
        return '';
    }

    return (toy_is_https_request() ? 'https://' : 'http://') . $host;
}

function toy_is_local_host(string $baseUrl): bool
{
    $host = parse_url($baseUrl, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return false;
    }

    return in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
}

function toy_is_http_url(string $url): bool
{
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
        return false;
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
}

function toy_is_public_http_url(string $url): bool
{
    if (!toy_is_http_url($url)) {
        return false;
    }

    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return false;
    }

    return toy_is_public_network_host($host);
}

function toy_is_public_network_host(string $host): bool
{
    $host = strtolower(trim($host, '[]'));
    if ($host === '' || $host === 'localhost') {
        return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    if (preg_match('/\A[a-z0-9.-]+\z/', $host) !== 1) {
        return false;
    }

    $addresses = gethostbynamel($host);
    if ($addresses === false || $addresses === []) {
        return false;
    }

    foreach ($addresses as $address) {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
    }

    return true;
}

function toy_request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function toy_request_path(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return '/';
    }

    $path = '/' . trim($path, '/');
    return $path === '/' ? '/' : $path;
}

function toy_is_installed(): bool
{
    return is_file(TOY_ROOT . '/config/config.php') && is_file(TOY_ROOT . '/storage/installed.lock');
}

function toy_load_config(): array
{
    $configFile = TOY_ROOT . '/config/config.php';
    if (!is_file($configFile)) {
        throw new RuntimeException('Config file does not exist.');
    }

    $config = include $configFile;
    if (!is_array($config)) {
        throw new RuntimeException('Config file must return an array.');
    }

    return $config;
}

function toy_apply_runtime_config(array $config): void
{
    $debug = !empty($config['debug']);
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);

    $logDir = TOY_ROOT . '/storage/logs';
    if ((is_dir($logDir) || mkdir($logDir, 0755, true)) && is_writable($logDir)) {
        ini_set('error_log', $logDir . '/error.log');
    }

    if (!empty($config['timezone']) && is_string($config['timezone'])) {
        date_default_timezone_set($config['timezone']);
    }
}

function toy_apply_site_runtime_settings(?array $site): void
{
    $timezone = is_array($site) ? (string) ($site['timezone'] ?? '') : '';
    if ($timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
        date_default_timezone_set($timezone);
    }
}

function toy_db(array $config): PDO
{
    $db = $config['db'] ?? [];
    if (!is_array($db)) {
        throw new RuntimeException('DB config is invalid.');
    }

    $host = (string) ($db['host'] ?? 'localhost');
    $name = (string) ($db['name'] ?? '');
    $user = (string) ($db['user'] ?? '');
    $password = (string) ($db['password'] ?? '');
    $charset = (string) ($db['charset'] ?? 'utf8mb4');

    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=' . $charset;
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function toy_client_ip(): string
{
    $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
        return '';
    }

    return $ipAddress;
}

function toy_client_user_agent(): string
{
    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (function_exists('mb_substr')) {
        return mb_substr($userAgent, 0, 500);
    }

    return substr($userAgent, 0, 500);
}

function toy_now(): string
{
    return date('Y-m-d H:i:s');
}

function toy_normalize_identifier(string $value): string
{
    return strtolower(trim($value));
}

function toy_send_mail(?array $site, string $to, string $subject, string $body): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        return false;
    }

    $baseUrl = is_array($site) ? (string) ($site['base_url'] ?? '') : '';
    $host = parse_url($baseUrl, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    $from = 'no-reply@' . preg_replace('/[^A-Za-z0-9.-]/', '', $host);
    $headers = [
        'From: ' . $from,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    return mail($to, $subject, $body, implode("\r\n", $headers));
}

function toy_hmac_hash(string $value, array $config): string
{
    $appKey = (string) ($config['app_key'] ?? '');
    if ($appKey === '') {
        throw new RuntimeException('app_key is required.');
    }

    return hash_hmac('sha256', $value, $appKey);
}
