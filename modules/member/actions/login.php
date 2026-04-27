<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$account = toy_member_current_account($pdo);
if ($account !== null) {
    toy_redirect('/admin');
}

$errors = [];
$identifier = '';

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $identifier = toy_post_string('identifier', 255);
    $password = toy_post_string('password', 255);
    $account = toy_member_find_by_identifier($pdo, $config, $identifier);

    if ($account !== null && $account['status'] === 'active' && password_verify($password, (string) $account['password_hash'])) {
        toy_member_login($pdo, $account);
        toy_member_log_auth($pdo, (int) $account['id'], 'login', 'success');
        toy_redirect('/admin');
    }

    toy_member_log_auth($pdo, $account !== null ? (int) $account['id'] : null, 'login', 'failure');
    $errors[] = '로그인 정보가 올바르지 않습니다.';
}

include TOY_ROOT . '/modules/member/views/login.php';
