<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

if (toy_request_method() !== 'POST') {
    toy_render_error(405, '허용되지 않는 요청입니다.');
    exit;
}

toy_require_csrf();

$account = toy_member_current_account($pdo);
if ($account !== null) {
    toy_member_log_auth($pdo, (int) $account['id'], 'logout', 'success');
    toy_audit_log($pdo, [
        'actor_account_id' => (int) $account['id'],
        'actor_type' => 'member',
        'event_type' => 'member.logout',
        'target_type' => 'member_account',
        'target_id' => (string) $account['id'],
        'result' => 'success',
        'message' => 'Member logged out.',
    ]);
}

toy_member_logout();
toy_redirect('/login');
