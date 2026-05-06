#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
define('TOY_ROOT', $root);

require_once $root . '/modules/member/helpers/accounts.php';
require_once $root . '/modules/member/helpers/tokens.php';
require_once $root . '/modules/member/helpers/throttle.php';

$errors = [];

function toy_member_auth_policy_error(string $message): void
{
    global $errors;
    $errors[] = $message;
}

function toy_member_auth_policy_assert(bool $condition, string $message): void
{
    if (!$condition) {
        toy_member_auth_policy_error($message);
    }
}

function toy_member_auth_policy_read(string $path): string
{
    global $root;

    $content = file_get_contents($root . '/' . $path);
    if (!is_string($content)) {
        toy_member_auth_policy_error('Cannot read file: ' . $path);
        return '';
    }

    return $content;
}

$unverifiedAccount = [
    'id' => 1,
    'status' => 'active',
    'email_verified_at' => null,
];
$verifiedAccount = [
    'id' => 1,
    'status' => 'active',
    'email_verified_at' => '2026-04-01 00:00:00',
];

toy_member_auth_policy_assert(
    toy_member_email_verification_blocks_login(['email_verification_enabled' => true], $unverifiedAccount),
    'Email verification should block active unverified accounts when enabled.'
);
toy_member_auth_policy_assert(
    !toy_member_email_verification_blocks_login(['email_verification_enabled' => false], $unverifiedAccount),
    'Email verification should not block login when disabled.'
);
toy_member_auth_policy_assert(
    !toy_member_email_verification_blocks_login(['email_verification_enabled' => true], $verifiedAccount),
    'Verified account should not be blocked by email verification policy.'
);
toy_member_auth_policy_assert(
    !toy_member_email_verification_blocks_login(['email_verification_enabled' => true], null),
    'Missing account should not be treated as email verification block.'
);

$_SESSION = [];
$sampleTokenHash = str_repeat('a', 64);
toy_member_store_password_reset_session_hash($sampleTokenHash);
toy_member_auth_policy_assert(
    toy_member_password_reset_session_hash(900) === $sampleTokenHash,
    'Password reset session hash should be readable within its lifetime.'
);
$_SESSION['toy_password_reset_token_stored_at'] = (string) (time() - 901);
toy_member_auth_policy_assert(
    toy_member_password_reset_session_hash(900) === '',
    'Password reset session hash should expire after its short lifetime.'
);
toy_member_auth_policy_assert(
    !isset($_SESSION['toy_password_reset_token_hash'], $_SESSION['toy_password_reset_token_stored_at']),
    'Expired password reset session hash should be cleared.'
);
toy_member_auth_policy_assert(
    in_array('login_email_unverified', toy_member_login_failure_event_types(), true),
    'Unverified email login blocks should count as login failure throttle events.'
);
toy_member_auth_policy_assert(
    in_array('password_change_reauth', toy_member_reauth_failure_event_types(), true)
        && in_array('withdraw_reauth', toy_member_reauth_failure_event_types(), true)
        && in_array('reauth_blocked', toy_member_reauth_failure_event_types(), true),
    'Sensitive reauth failures should count as reauth throttle events.'
);

$loginAction = toy_member_auth_policy_read('modules/member/actions/login.php');
if ($loginAction !== '') {
    toy_member_auth_policy_assert(
        strpos($loginAction, 'toy_member_email_verification_blocks_login') !== false,
        'Login action should enforce email verification policy.'
    );
    toy_member_auth_policy_assert(
        strpos($loginAction, 'toy_member_email_verification_throttle_status($pdo, (int) $account[\'id\'])') !== false
            && strpos($loginAction, 'toy_member_create_email_verification($pdo, $config, (int) $account[\'id\'], (string) $account[\'email\'])') !== false,
        'Login action should resend email verification within throttle limits after a valid password for an unverified account.'
    );
    toy_member_auth_policy_assert(
        strpos($loginAction, 'login_email_unverified') !== false,
        'Login action should log unverified email login blocks.'
    );
}

$accountHelper = toy_member_auth_policy_read('modules/member/helpers/accounts.php');
if ($accountHelper !== '') {
    toy_member_auth_policy_assert(
        strpos($accountHelper, 'toy_member_email_verification_blocks_login($settings, $account)') !== false,
        'Current member session should be rejected when email verification is still required.'
    );
    toy_member_auth_policy_assert(
        strpos($accountHelper, "if (!array_key_exists('toy_account_id', \$_SESSION)) {\n        return null;\n    }") !== false
            && strpos($accountHelper, "if (!is_int(\$accountId) && !ctype_digit((string) \$accountId)) {\n        toy_member_logout(\$pdo);\n        return null;\n    }") !== false
            && strpos($accountHelper, "if (\$accountId < 1) {\n        toy_member_logout(\$pdo);\n        return null;\n    }") !== false
            && strpos($accountHelper, "if (!is_array(\$account)) {\n        toy_member_logout(\$pdo);\n        return null;\n    }") !== false,
        'Current member account lookup should clear PHP session state when the session account is invalid or missing.'
    );
    toy_member_auth_policy_assert(
        strpos($accountHelper, 'function toy_member_rehash_login_password_if_needed') !== false
            && strpos($accountHelper, 'password_needs_rehash($currentHash, PASSWORD_DEFAULT)') !== false,
        'Login password rehash helper should upgrade stale password hashes.'
    );
}

$throttleHelper = toy_member_auth_policy_read('modules/member/helpers/throttle.php');
if ($throttleHelper !== '') {
    toy_member_auth_policy_assert(
        strpos($throttleHelper, 'toy_member_login_failure_event_types()') !== false,
        'Login throttle should use the shared login failure event list.'
    );
    toy_member_auth_policy_assert(
        strpos($throttleHelper, 'function toy_member_reauth_throttle_status') !== false
            && strpos($throttleHelper, 'member.reauth.account') !== false
            && strpos($throttleHelper, 'member.reauth.ip') !== false,
        'Sensitive reauth throttle should track account and IP failures.'
    );
}

$sessionHelper = toy_member_auth_policy_read('modules/member/helpers/sessions.php');
if ($sessionHelper !== '') {
    toy_member_auth_policy_assert(
        strpos($sessionHelper, 'function toy_member_rotate_current_session') !== false
            && strpos($sessionHelper, 'session_regenerate_id(true)') !== false
            && strpos($sessionHelper, 'toy_member_create_session($pdo, $accountId)') !== false,
        'Current member session rotation helper should regenerate PHP and member session tokens.'
    );
    toy_member_auth_policy_assert(
        strpos($sessionHelper, 'function toy_member_login(PDO $pdo, array $account): bool') !== false
            && strpos($sessionHelper, "if (\$sessionTokenHash !== '') {\n        \$_SESSION['toy_session_token_hash'] = \$sessionTokenHash;") !== false
            && strpos($sessionHelper, "unset(\$_SESSION['toy_session_token_hash']);") !== false,
        'Member login should clear stale session token hash when DB session creation fails.'
    );
    toy_member_auth_policy_assert(
        strpos($sessionHelper, 'toy_member_sessions_table_exists($pdo)') !== false
            && strpos($sessionHelper, "unset(\$_SESSION['toy_account_id']);") !== false
            && strpos($sessionHelper, 'return false;') !== false,
        'Member login should fail clearly when DB session creation fails while the session table exists.'
    );
    toy_member_auth_policy_assert(
        strpos($sessionHelper, 'function toy_member_revoke_account_sessions') !== false
            && strpos($sessionHelper, 'function toy_member_revoke_other_sessions') !== false
            && strpos($sessionHelper, 'return -1;') !== false,
        'Member session revocation helpers should distinguish DB failure from zero revoked sessions.'
    );
    toy_member_auth_policy_assert(
        strpos($sessionHelper, 'function toy_member_logout_current_session_if_account') !== false
            && strpos($sessionHelper, 'toy_member_current_session_account_id()') !== false,
        'Session helper should support immediate logout of the current session for a target account.'
    );
}

$accountAction = toy_member_auth_policy_read('modules/member/actions/account.php');
if ($accountAction !== '') {
    toy_member_auth_policy_assert(
        strpos($accountAction, 'toy_member_rotate_current_session($pdo, (int) $account[\'id\'])') !== false,
        'Password change should rotate the current member session.'
    );
    toy_member_auth_policy_assert(
        strpos($accountAction, 'if ($revokedSessions < 0)') !== false
            && strpos($accountAction, 'Other member sessions could not be revoked after password change.') !== false,
        'Password change should not silently continue when other sessions cannot be revoked.'
    );
    toy_member_auth_policy_assert(
        strpos($accountAction, 'toy_member_reauth_throttle_status($pdo, (int) $account[\'id\'])') !== false
            && strpos($accountAction, 'password_change_reauth') !== false,
        'Password change should throttle current-password reauth failures.'
    );
}

$withdrawAction = toy_member_auth_policy_read('modules/member/actions/withdraw.php');
if ($withdrawAction !== '') {
    toy_member_auth_policy_assert(
        strpos($withdrawAction, 'toy_member_reauth_throttle_status($pdo, (int) $account[\'id\'])') !== false
            && strpos($withdrawAction, 'withdraw_reauth') !== false,
        'Withdraw should throttle current-password reauth failures.'
    );
    toy_member_auth_policy_assert(
        strpos($withdrawAction, 'if ($revokedSessions < 0)') !== false
            && strpos($withdrawAction, 'Member sessions could not be revoked before account withdrawal.') !== false,
        'Withdraw should not continue when account sessions cannot be revoked.'
    );
}

$registerAction = toy_member_auth_policy_read('modules/member/actions/register.php');
if ($registerAction !== '') {
    toy_member_auth_policy_assert(
        strpos($registerAction, 'toy_member_login($pdo, $newAccount)') !== false
            && strpos($registerAction, '로그인 세션을 만들 수 없습니다') !== false,
        'Register action should keep auto-login for immediately verified accounts.'
    );
    toy_member_auth_policy_assert(
        strpos($registerAction, "toy_redirect('/login')") !== false
            && strpos($registerAction, '이메일 인증을 완료한 뒤 로그인하세요') !== false,
        'Register action should not auto-login unverified accounts.'
    );
}

$loginAction = toy_member_auth_policy_read('modules/member/actions/login.php');
if ($loginAction !== '') {
    toy_member_auth_policy_assert(
        strpos($loginAction, 'toy_member_rehash_login_password_if_needed') !== false,
        'Login action should rehash stale password hashes after successful verification.'
    );
    toy_member_auth_policy_assert(
        strpos($loginAction, 'if (toy_member_login($pdo, $account))') !== false
            && strpos($loginAction, 'login_session_failed') !== false,
        'Login action should not record login success when member session creation fails.'
    );
}

$paths = toy_member_auth_policy_read('modules/member/paths.php');
if ($paths !== '') {
    toy_member_auth_policy_assert(
        strpos($paths, "'GET /email/verified' => 'actions/email-verified.php'") !== false,
        'Email verification success route should be tokenless.'
    );
}

$emailVerifyAction = toy_member_auth_policy_read('modules/member/actions/email-verify.php');
if ($emailVerifyAction !== '') {
    toy_member_auth_policy_assert(
        strpos($emailVerifyAction, "toy_redirect('/email/verified')") !== false,
        'Email verification action should redirect to a tokenless success page.'
    );
}

$passwordResetAction = toy_member_auth_policy_read('modules/member/actions/password-reset.php');
if ($passwordResetAction !== '') {
    toy_member_auth_policy_assert(
        strpos($passwordResetAction, 'toy_member_store_password_reset_session_hash') !== false,
        'Password reset confirm action should keep only the reset token hash in session after initial validation.'
    );
    toy_member_auth_policy_assert(
        strpos($passwordResetAction, 'toy_member_password_reset_session_hash($resetTokenSessionSeconds)') !== false,
        'Password reset confirm action should enforce a short session hash lifetime.'
    );
    toy_member_auth_policy_assert(
        strpos($passwordResetAction, "toy_redirect('/password/reset/confirm')") !== false,
        'Password reset confirm action should redirect token query URLs to a tokenless form URL.'
    );
    toy_member_auth_policy_assert(
        strpos($passwordResetAction, 'toy_member_logout_current_session_if_account($pdo, (int) $reset[\'account_id\'])') !== false,
        'Password reset completion should immediately clear the current PHP session for the reset account.'
    );
    toy_member_auth_policy_assert(
        strpos($passwordResetAction, 'if ($revokedSessions < 0)') !== false
            && strpos($passwordResetAction, 'Member sessions could not be revoked after password reset.') !== false,
        'Password reset should not complete when account sessions cannot be revoked.'
    );
}

$tokenHelper = toy_member_auth_policy_read('modules/member/helpers/tokens.php');
if ($tokenHelper !== '') {
    toy_member_auth_policy_assert(
        strpos($tokenHelper, 'function toy_member_password_reset_token_hash') !== false,
        'Password reset token hash helper is missing.'
    );
    toy_member_auth_policy_assert(
        strpos($tokenHelper, 'function toy_member_password_reset_session_hash') !== false,
        'Password reset session hash helper is missing.'
    );
    toy_member_auth_policy_assert(
        strpos($tokenHelper, 'a.email AS account_email') !== false
            && strpos($tokenHelper, "toy_normalize_identifier((string) \$verification['email']) !== toy_normalize_identifier((string) \$verification['account_email'])") !== false
            && strpos($tokenHelper, 'AND email = :email') !== false,
        'Email verification should only verify the current account email that matches the issued token.'
    );
    toy_member_auth_policy_assert(
        strpos($tokenHelper, 'toy_password_reset_token_hash') !== false
            && strpos($tokenHelper, 'toy_password_reset_token_stored_at') !== false,
        'Password reset session should store hash and stored_at only.'
    );
    toy_member_auth_policy_assert(
        strpos($tokenHelper, 'toy_password_reset_token\'') === false
            && strpos($tokenHelper, 'toy_password_reset_token"') === false,
        'Password reset session should not store the raw reset token.'
    );
}

$passwordResetView = toy_member_auth_policy_read('modules/member/views/password-reset.php');
if ($passwordResetView !== '') {
    toy_member_auth_policy_assert(
        strpos($passwordResetView, 'name="token"') === false,
        'Password reset form should not render the reset token into HTML.'
    );
}

if ($errors !== []) {
    fwrite(STDERR, "member auth policy checks failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

echo "member auth policy checks completed.\n";
