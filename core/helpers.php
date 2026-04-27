<?php

declare(strict_types=1);

function toy_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
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
    error_reporting(E_ALL);

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
    $stmt = $pdo->query('SELECT * FROM toy_sites ORDER BY id ASC LIMIT 1');
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

function toy_redirect(string $url): void
{
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
