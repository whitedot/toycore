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
