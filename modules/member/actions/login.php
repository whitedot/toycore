<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$account = toy_member_current_account($pdo);
if ($account !== null) {
    toy_redirect('/account');
}

$errors = [];
$identifier = '';
$next = toy_member_safe_next_path(toy_get_string('next', 255));

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $identifier = toy_post_string('identifier', 255);
    $password = toy_post_string('password', 255);
    $next = toy_member_safe_next_path(toy_post_string('next', 255));
    $account = toy_member_find_by_identifier($pdo, $config, $identifier);
    $throttle = toy_member_login_throttle_status($pdo, $account !== null ? (int) $account['id'] : null);

    if (!empty($throttle['limited'])) {
        toy_member_log_auth($pdo, $account !== null ? (int) $account['id'] : null, 'login_blocked', 'failure');
        toy_audit_log($pdo, [
            'actor_account_id' => $account !== null ? (int) $account['id'] : null,
            'actor_type' => 'member',
            'event_type' => 'member.login.blocked',
            'target_type' => 'member_account',
            'target_id' => $account !== null ? (string) $account['id'] : '',
            'result' => 'failure',
            'message' => 'Member login blocked by throttle.',
        ]);
        $errors[] = '로그인 시도가 많습니다. 잠시 후 다시 시도하세요.';
    } elseif (toy_member_verify_login_password($account, $password)) {
        toy_member_login($pdo, $account);
        toy_member_log_auth($pdo, (int) $account['id'], 'login', 'success');
        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'member',
            'event_type' => 'member.login',
            'target_type' => 'member_account',
            'target_id' => (string) $account['id'],
            'result' => 'success',
            'message' => 'Member login succeeded.',
        ]);
        toy_redirect($next);
    } else {
        toy_member_log_auth($pdo, $account !== null ? (int) $account['id'] : null, 'login', 'failure');
        toy_audit_log($pdo, [
            'actor_account_id' => $account !== null ? (int) $account['id'] : null,
            'actor_type' => 'member',
            'event_type' => 'member.login',
            'target_type' => 'member_account',
            'target_id' => $account !== null ? (string) $account['id'] : '',
            'result' => 'failure',
            'message' => 'Member login failed.',
        ]);
        $errors[] = '로그인 정보가 올바르지 않습니다.';
    }
}

include TOY_ROOT . '/modules/member/views/login.php';
