<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$account = toy_member_require_login($pdo);
$errors = [];
$notice = '';
$emailVerificationUrl = '';

if (!empty($config['debug']) && !empty($_SESSION['toy_debug_email_verification_url']) && is_string($_SESSION['toy_debug_email_verification_url'])) {
    $emailVerificationUrl = $_SESSION['toy_debug_email_verification_url'];
}

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $currentPassword = toy_post_string('current_password', 255);
    $newPassword = toy_post_string('new_password', 255);
    $newPasswordConfirm = toy_post_string('new_password_confirm', 255);

    if (!password_verify($currentPassword, (string) $account['password_hash'])) {
        $errors[] = '현재 비밀번호가 올바르지 않습니다.';
    }

    if (strlen($newPassword) < 8) {
        $errors[] = '새 비밀번호는 8자 이상이어야 합니다.';
    }

    if ($newPassword !== $newPasswordConfirm) {
        $errors[] = '새 비밀번호 확인이 일치하지 않습니다.';
    }

    if ($errors === []) {
        toy_member_update_password($pdo, (int) $account['id'], $newPassword);
        toy_member_log_auth($pdo, (int) $account['id'], 'password_change', 'success');
        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'member',
            'event_type' => 'member.password.changed',
            'target_type' => 'member_account',
            'target_id' => (string) $account['id'],
            'result' => 'success',
            'message' => 'Member password changed.',
        ]);

        $account = toy_member_current_account($pdo);
        $notice = '비밀번호를 변경했습니다.';
    } else {
        toy_member_log_auth($pdo, (int) $account['id'], 'password_change', 'failure');
    }
}

$consents = toy_member_latest_consents($pdo, (int) $account['id']);

include TOY_ROOT . '/modules/member/views/account.php';
