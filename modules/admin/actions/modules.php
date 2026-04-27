<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$requiredModules = ['member', 'admin'];
$allowedStatuses = ['enabled', 'disabled'];
$errors = [];
$notice = '';

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $moduleKey = toy_post_string('module_key', 60);
    $status = toy_post_string('status', 30);

    if (preg_match('/\A[a-z0-9_]+\z/', $moduleKey) !== 1) {
        $errors[] = '모듈 키가 올바르지 않습니다.';
    }

    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = '모듈 상태 값이 올바르지 않습니다.';
    }

    if (in_array($moduleKey, $requiredModules, true) && $status !== 'enabled') {
        $errors[] = '기본 모듈은 비활성화할 수 없습니다.';
    }

    if ($errors === []) {
        $stmt = $pdo->prepare('SELECT id, status FROM toy_modules WHERE module_key = :module_key LIMIT 1');
        $stmt->execute(['module_key' => $moduleKey]);
        $module = $stmt->fetch();

        if (!is_array($module)) {
            $errors[] = '모듈을 찾을 수 없습니다.';
        }
    }

    if ($errors === []) {
        $stmt = $pdo->prepare(
            'UPDATE toy_modules
             SET status = :status, updated_at = :updated_at
             WHERE module_key = :module_key'
        );
        $stmt->execute([
            'status' => $status,
            'updated_at' => toy_now(),
            'module_key' => $moduleKey,
        ]);

        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'admin',
            'event_type' => 'module.status.updated',
            'target_type' => 'module',
            'target_id' => $moduleKey,
            'result' => 'success',
            'message' => 'Module status updated.',
            'metadata' => [
                'before_status' => (string) $module['status'],
                'after_status' => $status,
            ],
        ]);

        $notice = '모듈 상태를 저장했습니다.';
    }
}

$modules = [];
$stmt = $pdo->query('SELECT module_key, name, version, status, is_bundled, installed_at, updated_at FROM toy_modules ORDER BY id ASC');
foreach ($stmt->fetchAll() as $row) {
    $modules[] = $row;
}

include TOY_ROOT . '/modules/admin/views/modules.php';
