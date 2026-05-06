<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$account = toy_member_require_login($pdo);
$errors = [];

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $password = toy_post_string('password', 255);
    $confirmText = toy_post_string('confirm_text', 20);

    $reauthThrottle = toy_member_reauth_throttle_status($pdo, (int) $account['id']);
    if (!empty($reauthThrottle['limited'])) {
        $errors[] = '비밀번호 확인 시도가 많습니다. 잠시 후 다시 시도하세요.';
        toy_member_log_auth($pdo, (int) $account['id'], 'reauth_blocked', 'failure');
    } elseif (!password_verify($password, (string) $account['password_hash'])) {
        $errors[] = '비밀번호가 올바르지 않습니다.';
        toy_member_log_auth($pdo, (int) $account['id'], 'withdraw_reauth', 'failure');
    }

    if ($confirmText !== '탈퇴') {
        $errors[] = '확인 문구를 입력하세요.';
    }

    if ($errors === []) {
        $withdrawnConsents = 0;
        $pdo->beginTransaction();
        try {
            toy_member_delete_profile($pdo, (int) $account['id']);
            $revokedSessions = toy_member_revoke_account_sessions($pdo, (int) $account['id']);
            if ($revokedSessions < 0) {
                throw new RuntimeException('Member sessions could not be revoked before account withdrawal.');
            }
            $withdrawnConsents = toy_member_record_consent_withdrawals($pdo, (int) $account['id']);
            toy_member_anonymize_account($pdo, $config, (int) $account['id']);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        toy_member_log_auth($pdo, (int) $account['id'], 'withdraw', 'success');
        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'member',
            'event_type' => 'member.anonymized',
            'target_type' => 'member_account',
            'target_id' => (string) $account['id'],
            'result' => 'success',
            'message' => 'Member account withdrawn and anonymized.',
            'metadata' => [
                'revoked_sessions' => $revokedSessions,
                'withdrawn_consents' => $withdrawnConsents,
            ],
        ]);

        toy_member_logout($pdo);
        toy_redirect('/login');
    }
}

include TOY_ROOT . '/modules/member/views/withdraw.php';
