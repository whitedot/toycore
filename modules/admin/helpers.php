<?php

declare(strict_types=1);

function toy_admin_grant_role(PDO $pdo, int $accountId, string $roleKey): void
{
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO toy_admin_account_roles (account_id, role_key, created_at)
         VALUES (:account_id, :role_key, :created_at)'
    );
    $stmt->execute([
        'account_id' => $accountId,
        'role_key' => $roleKey,
        'created_at' => toy_now(),
    ]);
}

function toy_admin_revoke_role(PDO $pdo, int $accountId, string $roleKey): void
{
    $stmt = $pdo->prepare('DELETE FROM toy_admin_account_roles WHERE account_id = :account_id AND role_key = :role_key');
    $stmt->execute([
        'account_id' => $accountId,
        'role_key' => $roleKey,
    ]);
}

function toy_admin_current_roles(PDO $pdo, int $accountId): array
{
    $stmt = $pdo->prepare('SELECT role_key FROM toy_admin_account_roles WHERE account_id = :account_id ORDER BY role_key ASC');
    $stmt->execute(['account_id' => $accountId]);

    $roles = [];
    foreach ($stmt->fetchAll() as $row) {
        $roles[] = (string) $row['role_key'];
    }

    return $roles;
}

function toy_admin_has_role(PDO $pdo, int $accountId, array $allowedRoles): bool
{
    $roles = toy_admin_current_roles($pdo, $accountId);
    return array_intersect($roles, $allowedRoles) !== [];
}

function toy_admin_require_role(PDO $pdo, int $accountId, array $allowedRoles): void
{
    if (!toy_admin_has_role($pdo, $accountId, $allowedRoles)) {
        toy_render_error(403, '관리자 권한이 필요합니다.');
        exit;
    }
}

function toy_admin_owner_count(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT COUNT(*) AS count_value FROM toy_admin_account_roles WHERE role_key = 'owner'");
    $row = $stmt->fetch();
    return is_array($row) ? (int) $row['count_value'] : 0;
}

function toy_admin_update_files(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $paths = glob($directory . '/*.sql');
    if ($paths === false) {
        return [];
    }

    sort($paths, SORT_STRING);

    $updates = [];
    foreach ($paths as $path) {
        $version = basename($path, '.sql');
        if (preg_match('/\A\d{4}\.\d{2}\.\d{3}\z/', $version) !== 1) {
            continue;
        }

        $updates[] = [
            'version' => $version,
            'path' => $path,
            'checksum' => toy_admin_update_checksum($path),
        ];
    }

    return $updates;
}

function toy_admin_update_checksum(string $path): string
{
    if (!is_file($path)) {
        return '';
    }

    $checksum = hash_file('sha256', $path);
    return is_string($checksum) ? $checksum : '';
}

function toy_admin_update_path_is_allowed(array $update): bool
{
    $scope = (string) ($update['scope'] ?? '');
    $moduleKey = (string) ($update['module_key'] ?? '');
    $version = (string) ($update['version'] ?? '');
    $path = (string) ($update['path'] ?? '');

    if (preg_match('/\A\d{4}\.\d{2}\.\d{3}\z/', $version) !== 1 || !is_file($path)) {
        return false;
    }

    if ($scope === 'core') {
        $expectedDirectory = realpath(TOY_ROOT . '/database/core/updates');
        $expectedModuleKey = '';
    } elseif ($scope === 'module' && toy_is_safe_module_key($moduleKey)) {
        $expectedDirectory = realpath(TOY_ROOT . '/modules/' . $moduleKey . '/updates');
        $expectedModuleKey = $moduleKey;
    } else {
        return false;
    }

    if ($moduleKey !== $expectedModuleKey || $expectedDirectory === false) {
        return false;
    }

    $realPath = realpath($path);
    if ($realPath === false || strpos($realPath, $expectedDirectory . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }

    return basename($realPath) === $version . '.sql';
}

function toy_admin_acquire_update_lock(PDO $pdo): bool
{
    try {
        $stmt = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10) AS lock_acquired');
        $stmt->execute(['lock_name' => 'toycore_schema_updates']);
        $row = $stmt->fetch();
    } catch (Throwable $exception) {
        return false;
    }

    return is_array($row) && (string) ($row['lock_acquired'] ?? '') === '1';
}

function toy_admin_release_update_lock(PDO $pdo): void
{
    try {
        $stmt = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $stmt->execute(['lock_name' => 'toycore_schema_updates']);
    } catch (Throwable $ignored) {
    }
}

function toy_admin_applied_schema_versions(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT scope, module_key, version FROM toy_schema_versions');
    $applied = [];

    foreach ($stmt->fetchAll() as $row) {
        $key = (string) $row['scope'] . '|' . (string) $row['module_key'] . '|' . (string) $row['version'];
        $applied[$key] = true;
    }

    return $applied;
}

function toy_admin_schema_versions(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT scope, module_key, version, applied_at
         FROM toy_schema_versions
         ORDER BY scope ASC, module_key ASC, version ASC'
    );

    return $stmt->fetchAll();
}

function toy_admin_pending_updates(PDO $pdo): array
{
    $applied = toy_admin_applied_schema_versions($pdo);
    $pending = [];

    foreach (toy_admin_update_files(TOY_ROOT . '/database/core/updates') as $update) {
        $key = 'core||' . $update['version'];
        if (!isset($applied[$key])) {
            $pending[] = [
                'scope' => 'core',
                'module_key' => '',
                'label' => 'core',
                'version' => $update['version'],
                'path' => $update['path'],
                'checksum' => $update['checksum'],
            ];
        }
    }

    $stmt = $pdo->query('SELECT module_key FROM toy_modules ORDER BY module_key ASC');
    foreach ($stmt->fetchAll() as $module) {
        $moduleKey = (string) $module['module_key'];
        if (preg_match('/\A[a-z0-9_]+\z/', $moduleKey) !== 1) {
            continue;
        }

        foreach (toy_admin_update_files(TOY_ROOT . '/modules/' . $moduleKey . '/updates') as $update) {
            $key = 'module|' . $moduleKey . '|' . $update['version'];
            if (!isset($applied[$key])) {
                $pending[] = [
                    'scope' => 'module',
                    'module_key' => $moduleKey,
                    'label' => $moduleKey,
                    'version' => $update['version'],
                    'path' => $update['path'],
                    'checksum' => $update['checksum'],
                ];
            }
        }
    }

    return $pending;
}

function toy_admin_apply_update(PDO $pdo, array $update): void
{
    if (!toy_admin_update_path_is_allowed($update)) {
        throw new RuntimeException('Schema update path is invalid.');
    }

    $expectedChecksum = (string) ($update['checksum'] ?? '');
    if ($expectedChecksum !== '' && !hash_equals($expectedChecksum, toy_admin_update_checksum((string) $update['path']))) {
        throw new RuntimeException('Schema update checksum changed.');
    }

    toy_execute_sql_file($pdo, (string) $update['path']);
    toy_record_schema_version($pdo, (string) $update['scope'], (string) $update['module_key'], (string) $update['version']);
}
