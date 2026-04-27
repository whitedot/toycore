<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner']);

$allowedRoles = ['owner', 'admin', 'manager'];
$allowedActions = ['grant', 'revoke'];
$errors = [];
$notice = '';

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $targetAccountId = (int) toy_post_string('account_id', 20);
    $roleKey = toy_post_string('role_key', 40);
    $roleAction = toy_post_string('role_action', 20);

    if ($targetAccountId <= 0) {
        $errors[] = '계정을 선택하세요.';
    }

    if (!in_array($roleKey, $allowedRoles, true)) {
        $errors[] = '역할 값이 올바르지 않습니다.';
    }

    if (!in_array($roleAction, $allowedActions, true)) {
        $errors[] = '역할 작업 값이 올바르지 않습니다.';
    }

    if ($errors === []) {
        $stmt = $pdo->prepare('SELECT id, email FROM toy_member_accounts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $targetAccountId]);
        $targetAccount = $stmt->fetch();

        if (!is_array($targetAccount)) {
            $errors[] = '계정을 찾을 수 없습니다.';
        }
    }

    if ($errors === [] && $roleAction === 'revoke' && $roleKey === 'owner') {
        $targetRoles = toy_admin_current_roles($pdo, $targetAccountId);
        if (in_array('owner', $targetRoles, true) && toy_admin_owner_count($pdo) <= 1) {
            $errors[] = '마지막 owner 권한은 회수할 수 없습니다.';
        }
    }

    if ($errors === []) {
        if ($roleAction === 'grant') {
            toy_admin_grant_role($pdo, $targetAccountId, $roleKey);
            $eventType = 'admin.role.granted';
            $notice = '관리자 역할을 부여했습니다.';
        } else {
            toy_admin_revoke_role($pdo, $targetAccountId, $roleKey);
            $eventType = 'admin.role.revoked';
            $notice = '관리자 역할을 회수했습니다.';
        }

        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'admin',
            'event_type' => $eventType,
            'target_type' => 'member_account',
            'target_id' => (string) $targetAccountId,
            'result' => 'success',
            'message' => 'Admin role changed.',
            'metadata' => [
                'role_key' => $roleKey,
                'action' => $roleAction,
            ],
        ]);
    }
}

$accounts = [];
$stmt = $pdo->query(
    'SELECT a.id, a.email, a.display_name, a.status, GROUP_CONCAT(r.role_key ORDER BY r.role_key SEPARATOR ",") AS role_keys
     FROM toy_member_accounts a
     LEFT JOIN toy_admin_account_roles r ON r.account_id = a.id
     GROUP BY a.id, a.email, a.display_name, a.status
     ORDER BY a.id DESC
     LIMIT 100'
);

foreach ($stmt->fetchAll() as $row) {
    $roleKeys = (string) ($row['role_keys'] ?? '');
    $row['roles'] = $roleKeys === '' ? [] : explode(',', $roleKeys);
    $accounts[] = $row;
}

include TOY_ROOT . '/modules/admin/views/roles.php';
