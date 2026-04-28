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
    $allowExistingUpdate = !empty($data['allow_existing_update']);

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

    if (is_array($existing) && !$allowExistingUpdate) {
        throw new RuntimeException('Account already exists.');
    }

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

    $stmt = $pdo->prepare(
        'SELECT ' . toy_member_account_select_columns() . '
         FROM toy_member_accounts
         WHERE account_identifier_hash = :hash
         LIMIT 1'
    );
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

    if (!toy_member_session_is_current($pdo, (int) $accountId)) {
        toy_member_logout();
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT ' . toy_member_account_select_columns() . '
         FROM toy_member_accounts
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => (int) $accountId]);
    $account = $stmt->fetch();

    if (!is_array($account)) {
        return null;
    }

    if ((string) $account['status'] !== 'active') {
        toy_member_logout($pdo);
        return null;
    }

    return $account;
}

function toy_member_require_login(PDO $pdo): array
{
    $account = toy_member_current_account($pdo);
    if ($account === null) {
        $next = toy_request_path();
        toy_redirect('/login?next=' . rawurlencode($next));
    }

    return $account;
}

function toy_member_safe_next_path(string $path): string
{
    if (
        $path === ''
        || $path[0] !== '/'
        || str_starts_with($path, '//')
        || strpos($path, '\\') !== false
        || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
    ) {
        return '/account';
    }

    return $path;
}

function toy_member_verify_login_password(?array $account, string $password): bool
{
    $passwordHash = is_array($account)
        ? (string) ($account['password_hash'] ?? '')
        : toy_member_dummy_password_hash();

    $passwordMatches = password_verify($password, $passwordHash);

    return $passwordMatches
        && is_array($account)
        && (string) ($account['status'] ?? '') === 'active';
}

function toy_member_dummy_password_hash(): string
{
    return '$2y$10$rXJfqk3XCcK2njbFv2w3XuJ3Ny/E6.46vRsuNcSOHg65o0bfe4enK';
}

function toy_member_update_password(PDO $pdo, int $accountId, string $password): void
{
    $stmt = $pdo->prepare('UPDATE toy_member_accounts SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'updated_at' => toy_now(),
        'id' => $accountId,
    ]);
}

function toy_member_update_status(PDO $pdo, int $accountId, string $status): void
{
    $stmt = $pdo->prepare('UPDATE toy_member_accounts SET status = :status, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'updated_at' => toy_now(),
        'id' => $accountId,
    ]);
}

function toy_member_update_account_basics(PDO $pdo, int $accountId, string $displayName, string $locale): void
{
    $stmt = $pdo->prepare(
        'UPDATE toy_member_accounts
         SET display_name = :display_name,
             locale = :locale,
             updated_at = :updated_at
         WHERE id = :id'
    );
    $stmt->execute([
        'display_name' => $displayName,
        'locale' => $locale,
        'updated_at' => toy_now(),
        'id' => $accountId,
    ]);
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
        'ip_address' => toy_client_ip(),
        'user_agent' => toy_client_user_agent(),
        'created_at' => toy_now(),
    ]);
}
