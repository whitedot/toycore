<?php

declare(strict_types=1);

function toy_load_site(PDO $pdo): ?array
{
    $settings = toy_site_settings($pdo);

    return [
        'name' => (string) ($settings['site.name'] ?? 'Toycore'),
        'base_url' => (string) ($settings['site.base_url'] ?? ''),
        'timezone' => (string) ($settings['site.timezone'] ?? 'Asia/Seoul'),
        'default_locale' => (string) ($settings['site.default_locale'] ?? 'ko'),
        'status' => (string) ($settings['site.status'] ?? 'active'),
    ];
}

function toy_enabled_module_keys(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT module_key FROM toy_modules WHERE status = 'enabled' ORDER BY id ASC");
    $moduleKeys = [];
    foreach ($stmt->fetchAll() as $row) {
        $moduleKey = (string) ($row['module_key'] ?? '');
        if (toy_is_safe_module_key($moduleKey)) {
            $moduleKeys[] = $moduleKey;
        }
    }

    return $moduleKeys;
}

function toy_is_safe_module_key(string $moduleKey): bool
{
    return preg_match('/\A[a-z0-9_]+\z/', $moduleKey) === 1;
}

function toy_module_enabled(PDO $pdo, string $moduleKey): bool
{
    if (!toy_is_safe_module_key($moduleKey)) {
        return false;
    }

    return in_array($moduleKey, toy_enabled_module_keys($pdo), true);
}

function toy_module_type(string $moduleKey): string
{
    $metadata = toy_module_metadata($moduleKey);
    $type = (string) ($metadata['type'] ?? 'module');

    return in_array($type, ['module', 'plugin'], true) ? $type : 'module';
}

function toy_enabled_module_contract_files(PDO $pdo, string $contractFile, array $excludedModuleKeys = []): array
{
    if (preg_match('/\A[a-z0-9][a-z0-9_.-]{0,80}\.php\z/', $contractFile) !== 1) {
        return [];
    }

    $excluded = [];
    foreach ($excludedModuleKeys as $moduleKey) {
        if (is_string($moduleKey) && toy_is_safe_module_key($moduleKey)) {
            $excluded[$moduleKey] = true;
        }
    }

    $files = [];
    foreach (toy_enabled_module_keys($pdo) as $moduleKey) {
        if (isset($excluded[$moduleKey])) {
            continue;
        }

        $moduleDir = TOY_ROOT . '/modules/' . $moduleKey;
        $file = $moduleDir . '/' . $contractFile;
        if (!is_file($file)) {
            continue;
        }

        $realModuleDir = realpath($moduleDir);
        $realFile = realpath($file);
        if ($realModuleDir === false || $realFile === false || strpos($realFile, $realModuleDir . DIRECTORY_SEPARATOR) !== 0) {
            continue;
        }

        $files[$moduleKey] = $realFile;
    }

    return $files;
}

function toy_site_settings(PDO $pdo): array
{
    static $cache = [];
    static $cacheToken = null;

    $currentToken = (int) ($GLOBALS['toy_site_settings_cache_token'] ?? 0);
    if ($cacheToken !== $currentToken) {
        $cache = [];
        $cacheToken = $currentToken;
    }

    if (isset($cache['all'])) {
        return $cache['all'];
    }

    $stmt = $pdo->query('SELECT setting_key, setting_value, value_type FROM toy_site_settings ORDER BY setting_key ASC');

    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[(string) $row['setting_key']] = toy_cast_setting_value($row['setting_value'], (string) $row['value_type']);
    }

    $cache['all'] = $settings;
    return $settings;
}

function toy_clear_site_settings_cache(): void
{
    $GLOBALS['toy_site_settings_cache_token'] = (int) ($GLOBALS['toy_site_settings_cache_token'] ?? 0) + 1;
}

function toy_site_setting(PDO $pdo, string $key, mixed $default = null): mixed
{
    $settings = toy_site_settings($pdo);
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function toy_save_site_setting(PDO $pdo, string $key, string $value, string $valueType = 'string'): void
{
    if (preg_match('/\A[a-z][a-z0-9_.-]{1,119}\z/', $key) !== 1) {
        throw new InvalidArgumentException('Site setting key is invalid.');
    }

    if (!in_array($valueType, ['string', 'int', 'bool', 'json'], true)) {
        throw new InvalidArgumentException('Site setting value type is invalid.');
    }

    $now = toy_now();
    $stmt = $pdo->prepare(
        'INSERT INTO toy_site_settings
            (setting_key, setting_value, value_type, created_at, updated_at)
         VALUES
            (:setting_key, :setting_value, :value_type, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            value_type = VALUES(value_type),
            updated_at = VALUES(updated_at)'
    );
    $stmt->execute([
        'setting_key' => $key,
        'setting_value' => $value,
        'value_type' => $valueType,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    toy_clear_site_settings_cache();
}

function toy_save_site_settings(PDO $pdo, array $settings): void
{
    foreach ($settings as $key => $setting) {
        if (!is_array($setting)) {
            continue;
        }

        toy_save_site_setting(
            $pdo,
            (string) $key,
            (string) ($setting['value'] ?? ''),
            (string) ($setting['type'] ?? 'string')
        );
    }
}

function toy_module_settings(PDO $pdo, string $moduleKey): array
{
    static $cache = [];

    if (!toy_is_safe_module_key($moduleKey)) {
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

function toy_module_metadata(string $moduleKey): array
{
    static $cache = [];

    if (!toy_is_safe_module_key($moduleKey)) {
        return [];
    }

    if (isset($cache[$moduleKey])) {
        return $cache[$moduleKey];
    }

    $file = TOY_ROOT . '/modules/' . $moduleKey . '/module.php';
    if (!is_file($file)) {
        $cache[$moduleKey] = [];
        return [];
    }

    $metadata = include $file;
    $cache[$moduleKey] = is_array($metadata) ? $metadata : [];

    return $cache[$moduleKey];
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
