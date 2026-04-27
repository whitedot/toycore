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
        ];
    }

    return $updates;
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
            ];
        }
    }

    $stmt = $pdo->query("SELECT module_key FROM toy_modules WHERE status = 'enabled' ORDER BY module_key ASC");
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
                ];
            }
        }
    }

    return $pending;
}

function toy_admin_apply_update(PDO $pdo, array $update): void
{
    toy_execute_sql_file($pdo, (string) $update['path']);
    toy_record_schema_version($pdo, (string) $update['scope'], (string) $update['module_key'], (string) $update['version']);
}
