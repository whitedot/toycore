<?php

declare(strict_types=1);

function toy_member_create_password_reset(PDO $pdo, array $config, int $accountId): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = toy_hmac_hash($token, $config);
    $now = toy_now();
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $pdo->prepare(
        'UPDATE toy_member_password_resets
         SET used_at = :used_at
         WHERE account_id = :account_id
           AND used_at IS NULL'
    );
    $stmt->execute([
        'used_at' => $now,
        'account_id' => $accountId,
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO toy_member_password_resets (account_id, reset_token_hash, expires_at, created_at)
         VALUES (:account_id, :reset_token_hash, :expires_at, :created_at)'
    );
    $stmt->execute([
        'account_id' => $accountId,
        'reset_token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
        'created_at' => $now,
    ]);

    return $token;
}

function toy_member_find_password_reset(PDO $pdo, array $config, string $token): ?array
{
    if (preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1) {
        return null;
    }

    $tokenHash = toy_hmac_hash($token, $config);
    $stmt = $pdo->prepare(
        'SELECT r.id, r.account_id, r.reset_token_hash, r.expires_at, r.used_at, r.created_at,
                a.email, a.status
         FROM toy_member_password_resets r
         INNER JOIN toy_member_accounts a ON a.id = r.account_id
         WHERE r.reset_token_hash = :reset_token_hash
         LIMIT 1'
    );
    $stmt->execute(['reset_token_hash' => $tokenHash]);
    $reset = $stmt->fetch();

    if (!is_array($reset) || $reset['used_at'] !== null || (string) $reset['expires_at'] < toy_now()) {
        return null;
    }

    return $reset;
}

function toy_member_mark_password_reset_used(PDO $pdo, int $resetId): bool
{
    $stmt = $pdo->prepare(
        'UPDATE toy_member_password_resets
         SET used_at = :used_at
         WHERE id = :id
           AND used_at IS NULL'
    );
    $stmt->execute([
        'used_at' => toy_now(),
        'id' => $resetId,
    ]);

    return $stmt->rowCount() === 1;
}

function toy_member_create_email_verification(PDO $pdo, array $config, int $accountId, string $email): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = toy_hmac_hash($token, $config);
    $now = toy_now();
    $expiresAt = date('Y-m-d H:i:s', time() + 86400);

    $stmt = $pdo->prepare(
        'UPDATE toy_member_email_verifications
         SET verified_at = :verified_at
         WHERE account_id = :account_id
           AND verified_at IS NULL'
    );
    $stmt->execute([
        'verified_at' => $now,
        'account_id' => $accountId,
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO toy_member_email_verifications (account_id, email, verification_token_hash, expires_at, created_at)
         VALUES (:account_id, :email, :verification_token_hash, :expires_at, :created_at)'
    );
    $stmt->execute([
        'account_id' => $accountId,
        'email' => toy_normalize_identifier($email),
        'verification_token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
        'created_at' => $now,
    ]);

    return $token;
}

function toy_member_find_email_verification(PDO $pdo, array $config, string $token): ?array
{
    if (preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1) {
        return null;
    }

    $tokenHash = toy_hmac_hash($token, $config);
    $stmt = $pdo->prepare(
        'SELECT v.id, v.account_id, v.email, v.verification_token_hash, v.expires_at, v.verified_at, v.created_at,
                a.status
         FROM toy_member_email_verifications v
         INNER JOIN toy_member_accounts a ON a.id = v.account_id
         WHERE v.verification_token_hash = :verification_token_hash
         LIMIT 1'
    );
    $stmt->execute(['verification_token_hash' => $tokenHash]);
    $verification = $stmt->fetch();

    if (!is_array($verification) || $verification['verified_at'] !== null || (string) $verification['expires_at'] < toy_now()) {
        return null;
    }

    return $verification;
}

function toy_member_mark_email_verified(PDO $pdo, int $verificationId, int $accountId): bool
{
    $now = toy_now();
    $stmt = $pdo->prepare(
        'UPDATE toy_member_email_verifications
         SET verified_at = :verified_at
         WHERE id = :id
           AND verified_at IS NULL'
    );
    $stmt->execute([
        'verified_at' => $now,
        'id' => $verificationId,
    ]);
    if ($stmt->rowCount() !== 1) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE toy_member_accounts SET email_verified_at = :email_verified_at, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'email_verified_at' => $now,
        'updated_at' => $now,
        'id' => $accountId,
    ]);

    return true;
}
