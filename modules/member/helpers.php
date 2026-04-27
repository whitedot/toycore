<?php

declare(strict_types=1);

function toy_member_create_account(PDO $pdo, array $config, array $data): int
{
    $email = toy_normalize_identifier((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $displayName = trim((string) ($data['display_name'] ?? ''));
    $locale = trim((string) ($data['locale'] ?? 'ko'));
    $status = trim((string) ($data['status'] ?? 'active'));
    $emailVerifiedAt = $data['email_verified_at'] ?? null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Email is invalid.');
    }

    if ($password === '') {
        throw new InvalidArgumentException('Password is required.');
    }

    if ($displayName === '') {
        $displayName = $email;
    }

    $identifierHash = toy_hmac_hash($email, $config);
    $emailHash = toy_hmac_hash($email, $config);
    $now = toy_now();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT id FROM toy_member_accounts WHERE email_hash = :email_hash LIMIT 1');
    $stmt->execute(['email_hash' => $emailHash]);
    $existing = $stmt->fetch();

    if (is_array($existing)) {
        $stmt = $pdo->prepare(
            'UPDATE toy_member_accounts
             SET account_identifier_hash = :account_identifier_hash,
                 password_hash = :password_hash,
                 display_name = :display_name,
                 locale = :locale,
                 status = :status,
                 email_verified_at = :email_verified_at,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'account_identifier_hash' => $identifierHash,
            'password_hash' => $passwordHash,
            'display_name' => $displayName,
            'locale' => $locale,
            'status' => $status,
            'email_verified_at' => is_string($emailVerifiedAt) ? $emailVerifiedAt : null,
            'updated_at' => $now,
            'id' => (int) $existing['id'],
        ]);

        return (int) $existing['id'];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO toy_member_accounts
            (account_identifier_hash, login_id_hash, email, email_hash, password_hash, display_name, locale, status, email_verified_at, created_at, updated_at)
         VALUES
            (:account_identifier_hash, NULL, :email, :email_hash, :password_hash, :display_name, :locale, :status, :email_verified_at, :created_at, :updated_at)'
    );
    $stmt->execute([
        'account_identifier_hash' => $identifierHash,
        'email' => $email,
        'email_hash' => $emailHash,
        'password_hash' => $passwordHash,
        'display_name' => $displayName,
        'locale' => $locale,
        'status' => $status,
        'email_verified_at' => is_string($emailVerifiedAt) ? $emailVerifiedAt : null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return (int) $pdo->lastInsertId();
}

function toy_member_find_by_identifier(PDO $pdo, array $config, string $identifier): ?array
{
    $normalizedIdentifier = toy_normalize_identifier($identifier);
    $identifierHash = toy_hmac_hash($normalizedIdentifier, $config);

    $stmt = $pdo->prepare('SELECT * FROM toy_member_accounts WHERE account_identifier_hash = :hash LIMIT 1');
    $stmt->execute(['hash' => $identifierHash]);
    $account = $stmt->fetch();

    return is_array($account) ? $account : null;
}

function toy_member_current_account(PDO $pdo): ?array
{
    $accountId = $_SESSION['toy_account_id'] ?? null;
    if (!is_int($accountId) && !ctype_digit((string) $accountId)) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM toy_member_accounts WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $accountId]);
    $account = $stmt->fetch();

    return is_array($account) ? $account : null;
}

function toy_member_require_login(PDO $pdo): array
{
    $account = toy_member_current_account($pdo);
    if ($account === null) {
        toy_redirect('/login');
    }

    return $account;
}

function toy_member_login(PDO $pdo, array $account): void
{
    session_regenerate_id(true);
    $_SESSION['toy_account_id'] = (int) $account['id'];
    $_SESSION['toy_csrf_token'] = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare('UPDATE toy_member_accounts SET last_login_at = :last_login_at, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'last_login_at' => toy_now(),
        'updated_at' => toy_now(),
        'id' => (int) $account['id'],
    ]);
}

function toy_member_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function toy_member_log_auth(PDO $pdo, ?int $accountId, string $eventType, string $result): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO toy_member_auth_logs (account_id, event_type, result, ip_address, user_agent, created_at)
         VALUES (:account_id, :event_type, :result, :ip_address, :user_agent, :created_at)'
    );
    $stmt->execute([
        'account_id' => $accountId,
        'event_type' => $eventType,
        'result' => $result,
        'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'created_at' => toy_now(),
    ]);
}
