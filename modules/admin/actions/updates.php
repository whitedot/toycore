<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$errors = [];
$notice = '';
$appliedUpdates = [];

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $pendingUpdates = toy_admin_pending_updates($pdo);
    foreach ($pendingUpdates as $update) {
        try {
            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'schema.update.started',
                'target_type' => (string) $update['scope'],
                'target_id' => (string) $update['label'] . ':' . (string) $update['version'],
                'result' => 'success',
                'message' => 'Schema update started.',
            ]);

            toy_admin_apply_update($pdo, $update);
            $appliedUpdates[] = $update;

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'schema.update.completed',
                'target_type' => (string) $update['scope'],
                'target_id' => (string) $update['label'] . ':' . (string) $update['version'],
                'result' => 'success',
                'message' => 'Schema update completed.',
            ]);
        } catch (Throwable $exception) {
            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'schema.update.failed',
                'target_type' => (string) $update['scope'],
                'target_id' => (string) $update['label'] . ':' . (string) $update['version'],
                'result' => 'failure',
                'message' => 'Schema update failed.',
                'metadata' => [
                    'error' => $exception->getMessage(),
                ],
            ]);
            $errors[] = (string) $update['label'] . ' ' . (string) $update['version'] . ' 업데이트 중 오류가 발생했습니다.';
            break;
        }
    }

    if ($errors === []) {
        $notice = $appliedUpdates === [] ? '적용할 업데이트가 없습니다.' : '업데이트를 적용했습니다.';
    }
}

$pendingUpdates = toy_admin_pending_updates($pdo);

include TOY_ROOT . '/modules/admin/views/updates.php';
