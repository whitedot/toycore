<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$account = toy_member_require_login($pdo);

if (toy_request_method() !== 'POST') {
    toy_render_error(405, '허용되지 않는 요청입니다.');
    exit;
}

toy_require_csrf();

if ($account['email_verified_at'] === null) {
    $token = toy_member_create_email_verification($pdo, $config, (int) $account['id'], (string) $account['email']);
    $_SESSION['toy_debug_email_verification_url'] = '/email/verify?token=' . rawurlencode($token);
    toy_member_log_auth($pdo, (int) $account['id'], 'email_verification_request', 'success');
    toy_audit_log($pdo, [
        'actor_account_id' => (int) $account['id'],
        'actor_type' => 'member',
        'event_type' => 'member.email_verification.requested',
        'target_type' => 'member_account',
        'target_id' => (string) $account['id'],
        'result' => 'success',
        'message' => 'Member email verification requested.',
    ]);
}

toy_redirect('/account');
