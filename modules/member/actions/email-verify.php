<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$token = toy_get_string('token', 80);
$verification = toy_member_find_email_verification($pdo, $config, $token);

if ($verification === null || $verification['status'] !== 'active') {
    toy_render_error(400, '이메일 인증 링크가 올바르지 않거나 만료되었습니다.');
    exit;
}

toy_member_mark_email_verified($pdo, (int) $verification['id'], (int) $verification['account_id']);
toy_member_log_auth($pdo, (int) $verification['account_id'], 'email_verification', 'success');
toy_audit_log($pdo, [
    'actor_account_id' => (int) $verification['account_id'],
    'actor_type' => 'member',
    'event_type' => 'member.email.verified',
    'target_type' => 'member_account',
    'target_id' => (string) $verification['account_id'],
    'result' => 'success',
    'message' => 'Member email verified.',
]);

unset($_SESSION['toy_debug_email_verification_url']);

include TOY_ROOT . '/modules/member/views/email-verified.php';
