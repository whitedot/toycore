<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$account = toy_member_require_login($pdo);
$errors = [];

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $password = toy_post_string('password', 255);
    $confirmText = toy_post_string('confirm_text', 20);

    if (!password_verify($password, (string) $account['password_hash'])) {
        $errors[] = '비밀번호가 올바르지 않습니다.';
    }

    if ($confirmText !== '탈퇴') {
        $errors[] = '확인 문구를 입력하세요.';
    }

    if ($errors === []) {
        toy_member_update_status($pdo, (int) $account['id'], 'withdrawn');
        toy_member_log_auth($pdo, (int) $account['id'], 'withdraw', 'success');
        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'member',
            'event_type' => 'member.withdrawn',
            'target_type' => 'member_account',
            'target_id' => (string) $account['id'],
            'result' => 'success',
            'message' => 'Member account withdrawn.',
        ]);

        toy_member_logout();
        toy_redirect('/login');
    }
}

include TOY_ROOT . '/modules/member/views/withdraw.php';
