#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
define('TOY_ROOT', $root);

require_once $root . '/modules/member/helpers/accounts.php';
require_once $root . '/modules/member/helpers/tokens.php';

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

$loginAction = toy_member_auth_policy_read('modules/member/actions/login.php');
if ($loginAction !== '') {
    toy_member_auth_policy_assert(
        strpos($loginAction, 'toy_member_email_verification_blocks_login') !== false,
        'Login action should enforce email verification policy.'
    );
    toy_member_auth_policy_assert(
        strpos($loginAction, 'login_email_unverified') !== false,
        'Login action should log unverified email login blocks.'
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
