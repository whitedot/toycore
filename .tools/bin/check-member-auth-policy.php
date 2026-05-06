#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
define('TOY_ROOT', $root);

require_once $root . '/modules/member/helpers/accounts.php';

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

if ($errors !== []) {
    fwrite(STDERR, "member auth policy checks failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

echo "member auth policy checks completed.\n";
