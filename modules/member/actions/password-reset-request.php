<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$errors = [];
$notice = '';
$resetUrl = '';
$email = '';

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $email = toy_post_string('email', 255);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '이메일 형식이 올바르지 않습니다.';
    }

    if ($errors === []) {
        $account = toy_member_find_by_identifier($pdo, $config, $email);
        if ($account !== null && $account['status'] === 'active') {
            $token = toy_member_create_password_reset($pdo, $config, (int) $account['id']);
            $resetUrl = '/password/reset/confirm?token=' . rawurlencode($token);
            toy_member_log_auth($pdo, (int) $account['id'], 'password_reset_request', 'success');
            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'member',
                'event_type' => 'member.password_reset.requested',
                'target_type' => 'member_account',
                'target_id' => (string) $account['id'],
                'result' => 'success',
                'message' => 'Member password reset requested.',
            ]);
        }

        $notice = '입력한 이메일로 비밀번호 재설정 안내를 보냈습니다.';
    }
}

include TOY_ROOT . '/modules/member/views/password-reset-request.php';
