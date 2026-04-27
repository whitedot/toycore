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

function toy_load_site(PDO $pdo): ?array
{
    $stmt = $pdo->query(
        'SELECT id, site_key, name, base_url, timezone, default_locale, status, created_at, updated_at
         FROM toy_sites
         ORDER BY id ASC
         LIMIT 1'
    );
    $site = $stmt->fetch();
    return is_array($site) ? $site : null;
}

function toy_enabled_module_keys(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT module_key FROM toy_modules WHERE status = 'enabled' ORDER BY id ASC");
    $moduleKeys = [];
    foreach ($stmt->fetchAll() as $row) {
        $moduleKey = (string) ($row['module_key'] ?? '');
        if (preg_match('/\A[a-z0-9_]+\z/', $moduleKey) === 1) {
            $moduleKeys[] = $moduleKey;
        }
    }

    return $moduleKeys;
}

function toy_site_settings(PDO $pdo, bool $publicOnly = false): array
{
    static $cache = [];

    $cacheKey = $publicOnly ? 'public' : 'all';
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    if ($publicOnly) {
        $stmt = $pdo->query('SELECT setting_key, setting_value, value_type FROM toy_site_settings WHERE is_public = 1 ORDER BY setting_key ASC');
    } else {
        $stmt = $pdo->query('SELECT setting_key, setting_value, value_type FROM toy_site_settings ORDER BY setting_key ASC');
    }

    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[(string) $row['setting_key']] = toy_cast_setting_value($row['setting_value'], (string) $row['value_type']);
    }

    $cache[$cacheKey] = $settings;
    return $settings;
}

function toy_site_setting(PDO $pdo, string $key, mixed $default = null): mixed
{
    $settings = toy_site_settings($pdo);
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function toy_module_settings(PDO $pdo, string $moduleKey): array
{
    static $cache = [];

    if (preg_match('/\A[a-z0-9_]+\z/', $moduleKey) !== 1) {
        return [];
    }

    if (isset($cache[$moduleKey])) {
        return $cache[$moduleKey];
    }

    $stmt = $pdo->prepare(
        'SELECT s.setting_key, s.setting_value, s.value_type
         FROM toy_module_settings s
         INNER JOIN toy_modules m ON m.id = s.module_id
         WHERE m.module_key = :module_key
         ORDER BY s.setting_key ASC'
    );
    $stmt->execute(['module_key' => $moduleKey]);

    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[(string) $row['setting_key']] = toy_cast_setting_value($row['setting_value'], (string) $row['value_type']);
    }

    $cache[$moduleKey] = $settings;
    return $settings;
}

function toy_module_setting(PDO $pdo, string $moduleKey, string $key, mixed $default = null): mixed
{
    $settings = toy_module_settings($pdo, $moduleKey);
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function toy_cast_setting_value(mixed $value, string $type): mixed
{
    if ($type === 'int') {
        return (int) $value;
    }

    if ($type === 'bool') {
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    if ($type === 'json') {
        $decoded = json_decode((string) $value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    return $value === null ? '' : (string) $value;
}

function toy_set_locale(string $locale): void
{
    if (preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/', $locale) !== 1) {
        $locale = 'ko';
    }

    $GLOBALS['toy_locale'] = $locale;
}

function toy_locale(): string
{
    $locale = $GLOBALS['toy_locale'] ?? 'ko';
    return is_string($locale) && $locale !== '' ? $locale : 'ko';
}

function toy_resolve_locale(PDO $pdo, ?array $site): string
{
    $accountId = $_SESSION['toy_account_id'] ?? null;
    if (is_int($accountId) || ctype_digit((string) $accountId)) {
        try {
            $stmt = $pdo->prepare('SELECT locale FROM toy_member_accounts WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int) $accountId]);
            $account = $stmt->fetch();
            if (is_array($account) && is_string($account['locale'] ?? null) && $account['locale'] !== '') {
                return (string) $account['locale'];
            }
        } catch (Throwable $exception) {
            return is_array($site) ? (string) ($site['default_locale'] ?? 'ko') : 'ko';
        }
    }

    return is_array($site) ? (string) ($site['default_locale'] ?? 'ko') : 'ko';
}

function toy_t(string $key, array $params = [], ?string $locale = null): string
{
    $locale = $locale ?? toy_locale();
    $moduleKey = '';
    $translationKey = $key;

    if (strpos($key, '::') !== false) {
        [$moduleKey, $translationKey] = explode('::', $key, 2);
    }

    $translations = toy_load_translations($locale, $moduleKey);
    $message = isset($translations[$translationKey]) && is_string($translations[$translationKey])
        ? $translations[$translationKey]
        : $key;

    foreach ($params as $name => $value) {
        $message = str_replace('{' . $name . '}', (string) $value, $message);
    }

    return $message;
}

function toy_load_translations(string $locale, string $moduleKey = ''): array
{
    static $cache = [];

    if (preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/', $locale) !== 1) {
        $locale = 'ko';
    }

    if ($moduleKey !== '' && preg_match('/\A[a-z0-9_]+\z/', $moduleKey) !== 1) {
        return [];
    }

    $cacheKey = $moduleKey . '|' . $locale;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $file = $moduleKey === ''
        ? TOY_ROOT . '/lang/' . $locale . '/core.php'
        : TOY_ROOT . '/modules/' . $moduleKey . '/lang/' . $locale . '.php';

    if (!is_file($file)) {
        $cache[$cacheKey] = [];
        return [];
    }

    $translations = include $file;
    $cache[$cacheKey] = is_array($translations) ? $translations : [];

    return $cache[$cacheKey];
}

function toy_is_safe_module_action(string $path): bool
{
    if ($path === '' || strpos($path, '..') !== false || strpos($path, '\\') !== false) {
        return false;
    }

    return preg_match('/\Aactions\/[a-z0-9_\-\/]+\.php\z/', $path) === 1;
}

function toy_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function toy_stylesheet_tag(): string
{
    return '<link rel="stylesheet" href="/assets/toycore.css">';
}

function toy_redirect(string $url): void
{
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
        toy_render_error(500, '리다이렉트 URL이 올바르지 않습니다.');
        exit;
    }

    header('Location: ' . $url, true, 302);
    exit;
}

function toy_csrf_token(): string
{
    if (empty($_SESSION['toy_csrf_token']) || !is_string($_SESSION['toy_csrf_token'])) {
        $_SESSION['toy_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['toy_csrf_token'];
}

function toy_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . toy_e(toy_csrf_token()) . '">';
}

function toy_require_csrf(): void
{
    $expected = $_SESSION['toy_csrf_token'] ?? '';
    $actual = $_POST['csrf_token'] ?? '';

    if (!is_string($expected) || !is_string($actual) || $expected === '' || !hash_equals($expected, $actual)) {
        toy_render_error(400, '요청 보안 토큰이 올바르지 않습니다.');
        exit;
    }
}

function toy_post_string(string $key, int $maxLength): string
{
    $value = $_POST[$key] ?? '';
    if (is_array($value)) {
        return '';
    }

    $value = trim((string) $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function toy_get_string(string $key, int $maxLength): string
{
    $value = $_GET[$key] ?? '';
    if (is_array($value)) {
        return '';
    }

    $value = trim((string) $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function toy_now(): string
{
    return date('Y-m-d H:i:s');
}

function toy_normalize_identifier(string $value): string
{
    return strtolower(trim($value));
}

function toy_absolute_url(?array $site, string $path): string
{
    $baseUrl = is_array($site) ? rtrim((string) ($site['base_url'] ?? ''), '/') : '';
    if ($baseUrl === '' || !toy_is_http_url($baseUrl)) {
        return $path;
    }

    return $baseUrl . '/' . ltrim($path, '/');
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

function toy_execute_sql_file(PDO $pdo, string $file): void
{
    if (!is_file($file)) {
        throw new RuntimeException('SQL file does not exist: ' . $file);
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('SQL file cannot be read: ' . $file);
    }

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

function toy_fetch_http_response(string $url): ?array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 3,
            'ignore_errors' => true,
            'header' => "User-Agent: Toycore-Install-Check\r\n",
        ],
    ]);

    set_error_handler(static function (): bool {
        return true;
    });
    $body = file_get_contents($url, false, $context);
    restore_error_handler();

    if ($body === false || empty($http_response_header) || !is_array($http_response_header)) {
        return null;
    }

    foreach ($http_response_header as $header) {
        if (preg_match('/\AHTTP\/\S+\s+(\d{3})\b/', (string) $header, $matches) === 1) {
            return [
                'status' => (int) $matches[1],
                'body' => $body,
            ];
        }
    }

    return null;
}

function toy_internal_access_check_urls(string $baseUrl): array
{
    $baseUrl = rtrim($baseUrl, '/');
    if ($baseUrl === '') {
        return [];
    }

    $checks = [
        '/AGENTS.md' => '/# AGENTS\.md/',
        '/database/core/install.sql' => '/CREATE TABLE IF NOT EXISTS toy_sites/',
        '/modules/member/install.sql' => '/CREATE TABLE IF NOT EXISTS toy_member_accounts/',
        '/docs/deployment-protection.md' => '/# 배포 보호 기준/',
        '/.git/HEAD' => '/\A(?:ref: refs\/|[a-f0-9]{40})/',
    ];

    $urls = [];
    foreach ($checks as $path => $pattern) {
        $urls[] = [
            'url' => $baseUrl . $path,
            'pattern' => $pattern,
        ];
    }

    return $urls;
}

function toy_public_internal_access_findings(string $baseUrl): array
{
    $findings = [];
    foreach (toy_internal_access_check_urls($baseUrl) as $check) {
        $response = toy_fetch_http_response((string) $check['url']);
        if (
            is_array($response)
            && (int) $response['status'] >= 200
            && (int) $response['status'] < 400
            && preg_match((string) $check['pattern'], (string) $response['body']) === 1
        ) {
            $findings[] = [
                'url' => (string) $check['url'],
                'status' => (int) $response['status'],
            ];
        }
    }

    return $findings;
}

function toy_record_schema_version(PDO $pdo, string $scope, string $moduleKey, string $version): void
{
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO toy_schema_versions (scope, module_key, version, applied_at)
         VALUES (:scope, :module_key, :version, :applied_at)'
    );
    $stmt->execute([
        'scope' => $scope,
        'module_key' => $moduleKey,
        'version' => $version,
        'applied_at' => toy_now(),
    ]);
}

function toy_write_config(array $config): void
{
    $configDir = TOY_ROOT . '/config';
    if (!is_dir($configDir) && !mkdir($configDir, 0755, true)) {
        throw new RuntimeException('config directory cannot be created.');
    }

    $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
    $target = $configDir . '/config.php';
    $temporary = $target . '.tmp';

    if (file_put_contents($temporary, $content, LOCK_EX) === false) {
        throw new RuntimeException('config file cannot be written.');
    }

    if (!rename($temporary, $target)) {
        throw new RuntimeException('config file cannot be moved into place.');
    }
}

function toy_render_error(int $statusCode, string $message, ?Throwable $exception = null): void
{
    http_response_code($statusCode);
    if ($exception instanceof Throwable) {
        toy_log_exception($exception, 'render_error_' . $statusCode);
    }

    $config = [];
    if (is_file(TOY_ROOT . '/config/config.php')) {
        try {
            $config = toy_load_config();
        } catch (Throwable $ignored) {
            $config = [];
        }
    }

    $debug = !empty($config['debug']);
    $pageTitle = (string) $statusCode;
    include TOY_ROOT . '/core/views/error.php';
}

function toy_log_exception(Throwable $exception, string $context): void
{
    $logDir = TOY_ROOT . '/storage/logs';
    if (!is_dir($logDir) && !mkdir($logDir, 0755, true)) {
        return;
    }

    $line = sprintf(
        "[%s] %s %s: %s in %s:%d\n",
        toy_now(),
        $context,
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );

    file_put_contents($logDir . '/error.log', $line, FILE_APPEND | LOCK_EX);
}

function toy_audit_log(PDO $pdo, array $data): void
{
    try {
        $metadata = $data['metadata'] ?? null;
        $metadataJson = null;
        if (is_array($metadata) && $metadata !== []) {
            $encoded = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $metadataJson = is_string($encoded) ? $encoded : null;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO toy_audit_logs
                (actor_account_id, actor_type, event_type, target_type, target_id, result, ip_address, user_agent, message, metadata_json, created_at)
             VALUES
                (:actor_account_id, :actor_type, :event_type, :target_type, :target_id, :result, :ip_address, :user_agent, :message, :metadata_json, :created_at)'
        );
        $stmt->execute([
            'actor_account_id' => isset($data['actor_account_id']) ? (int) $data['actor_account_id'] : null,
            'actor_type' => (string) ($data['actor_type'] ?? 'system'),
            'event_type' => (string) ($data['event_type'] ?? ''),
            'target_type' => (string) ($data['target_type'] ?? ''),
            'target_id' => (string) ($data['target_id'] ?? ''),
            'result' => (string) ($data['result'] ?? 'success'),
            'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'message' => (string) ($data['message'] ?? ''),
            'metadata_json' => $metadataJson,
            'created_at' => toy_now(),
        ]);
    } catch (Throwable $ignored) {
    }
}
