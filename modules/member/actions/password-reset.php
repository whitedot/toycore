<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$errors = [];
$notice = '';
$token = toy_request_method() === 'POST' ? toy_post_string('token', 80) : toy_get_string('token', 80);
$reset = toy_member_find_password_reset($pdo, $config, $token);

if ($reset === null) {
    toy_render_error(400, '비밀번호 재설정 링크가 올바르지 않거나 만료되었습니다.');
    exit;
}

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $reset = toy_member_find_password_reset($pdo, $config, $token);
    if ($reset === null) {
        toy_render_error(400, '비밀번호 재설정 링크가 올바르지 않거나 만료되었습니다.');
        exit;
    }

    $password = toy_post_string('password', 255);
    $passwordConfirm = toy_post_string('password_confirm', 255);

    if (strlen($password) < 8) {
        $errors[] = '새 비밀번호는 8자 이상이어야 합니다.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = '새 비밀번호 확인이 일치하지 않습니다.';
    }

    if ($reset['status'] !== 'active') {
        $errors[] = '활성 계정만 비밀번호를 재설정할 수 있습니다.';
    }

    if ($errors === []) {
        toy_member_update_password($pdo, (int) $reset['account_id'], $password);
        toy_member_mark_password_reset_used($pdo, (int) $reset['id']);
        $revokedSessions = toy_member_revoke_account_sessions($pdo, (int) $reset['account_id']);
        toy_member_log_auth($pdo, (int) $reset['account_id'], 'password_reset', 'success');
        toy_audit_log($pdo, [
            'actor_account_id' => (int) $reset['account_id'],
            'actor_type' => 'member',
            'event_type' => 'member.password_reset.completed',
            'target_type' => 'member_account',
            'target_id' => (string) $reset['account_id'],
            'result' => 'success',
            'message' => 'Member password reset completed.',
            'metadata' => [
                'revoked_sessions' => $revokedSessions,
            ],
        ]);

        $notice = '비밀번호를 재설정했습니다. 새 비밀번호로 로그인하세요.';
    }
}

include TOY_ROOT . '/modules/member/views/password-reset.php';
