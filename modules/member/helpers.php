<?php

declare(strict_types=1);

function toy_member_account_select_columns(): string
{
    return 'id, account_identifier_hash, login_id_hash, email, email_hash, password_hash, display_name, locale, status, email_verified_at, last_login_at, created_at, updated_at';
}

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

    return is_array($account) ? $account : null;
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
    if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || strpos($path, '\\') !== false) {
        return '/account';
    }

    return $path;
}

function toy_member_login(PDO $pdo, array $account): void
{
    session_regenerate_id(true);
    $_SESSION['toy_account_id'] = (int) $account['id'];
    $_SESSION['toy_csrf_token'] = bin2hex(random_bytes(32));
    $sessionTokenHash = toy_member_create_session($pdo, (int) $account['id']);
    if ($sessionTokenHash !== '') {
        $_SESSION['toy_session_token_hash'] = $sessionTokenHash;
    }

    $stmt = $pdo->prepare('UPDATE toy_member_accounts SET last_login_at = :last_login_at, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'last_login_at' => toy_now(),
        'updated_at' => toy_now(),
        'id' => (int) $account['id'],
    ]);
}

function toy_member_create_session(PDO $pdo, int $accountId): string
{
    $sessionTokenHash = hash('sha256', bin2hex(random_bytes(32)));
    $now = toy_now();
    $expiresAt = date('Y-m-d H:i:s', time() + 86400);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO toy_member_sessions
                (account_id, session_token_hash, remember_token_hash, ip_address, user_agent, expires_at, created_at, last_seen_at)
             VALUES
                (:account_id, :session_token_hash, NULL, :ip_address, :user_agent, :expires_at, :created_at, :last_seen_at)'
        );
        $stmt->execute([
            'account_id' => $accountId,
            'session_token_hash' => $sessionTokenHash,
            'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'last_seen_at' => $now,
        ]);
    } catch (PDOException $exception) {
        return '';
    }

    return $sessionTokenHash;
}

function toy_member_session_is_current(PDO $pdo, int $accountId): bool
{
    $sessionTokenHash = $_SESSION['toy_session_token_hash'] ?? '';
    if (!is_string($sessionTokenHash) || preg_match('/\A[a-f0-9]{64}\z/', $sessionTokenHash) !== 1) {
        return !toy_member_sessions_table_exists($pdo);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, expires_at, revoked_at, last_seen_at
             FROM toy_member_sessions
             WHERE account_id = :account_id
               AND session_token_hash = :session_token_hash
             LIMIT 1'
        );
        $stmt->execute([
            'account_id' => $accountId,
            'session_token_hash' => $sessionTokenHash,
        ]);
        $session = $stmt->fetch();
    } catch (PDOException $exception) {
        return false;
    }

    if (!is_array($session) || $session['revoked_at'] !== null || (string) $session['expires_at'] < toy_now()) {
        return false;
    }

    $lastSeenAt = strtotime((string) $session['last_seen_at']);
    if ($lastSeenAt === false || $lastSeenAt <= time() - 300) {
        $stmt = $pdo->prepare('UPDATE toy_member_sessions SET last_seen_at = :last_seen_at WHERE id = :id');
        $stmt->execute([
            'last_seen_at' => toy_now(),
            'id' => (int) $session['id'],
        ]);
    }

    return true;
}

function toy_member_sessions_table_exists(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM toy_member_sessions LIMIT 1');
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function toy_member_revoke_current_session(PDO $pdo): void
{
    $sessionTokenHash = $_SESSION['toy_session_token_hash'] ?? '';
    if (!is_string($sessionTokenHash) || preg_match('/\A[a-f0-9]{64}\z/', $sessionTokenHash) !== 1) {
        return;
    }

    try {
        $stmt = $pdo->prepare('UPDATE toy_member_sessions SET revoked_at = :revoked_at WHERE session_token_hash = :session_token_hash AND revoked_at IS NULL');
        $stmt->execute([
            'revoked_at' => toy_now(),
            'session_token_hash' => $sessionTokenHash,
        ]);
    } catch (PDOException $exception) {
        return;
    }
}

function toy_member_revoke_account_sessions(PDO $pdo, int $accountId): int
{
    try {
        $stmt = $pdo->prepare(
            'UPDATE toy_member_sessions
             SET revoked_at = :revoked_at
             WHERE account_id = :account_id
               AND revoked_at IS NULL'
        );
        $stmt->execute([
            'revoked_at' => toy_now(),
            'account_id' => $accountId,
        ]);
    } catch (PDOException $exception) {
        return 0;
    }

    return $stmt->rowCount();
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

function toy_member_empty_profile(): array
{
    return [
        'nickname' => '',
        'phone' => '',
        'birth_date' => '',
        'avatar_path' => '',
        'profile_text' => '',
    ];
}

function toy_member_profile(PDO $pdo, int $accountId): array
{
    $stmt = $pdo->prepare(
        'SELECT nickname, phone, birth_date, avatar_path, profile_text
         FROM toy_member_profiles
         WHERE account_id = :account_id
         LIMIT 1'
    );
    $stmt->execute(['account_id' => $accountId]);
    $profile = $stmt->fetch();

    if (!is_array($profile)) {
        return toy_member_empty_profile();
    }

    return [
        'nickname' => (string) $profile['nickname'],
        'phone' => (string) $profile['phone'],
        'birth_date' => is_string($profile['birth_date']) ? $profile['birth_date'] : '',
        'avatar_path' => (string) $profile['avatar_path'],
        'profile_text' => (string) ($profile['profile_text'] ?? ''),
    ];
}

function toy_member_save_profile(PDO $pdo, int $accountId, array $profile): void
{
    $now = toy_now();
    $birthDate = trim((string) ($profile['birth_date'] ?? ''));
    if ($birthDate === '') {
        $birthDate = null;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO toy_member_profiles
            (account_id, nickname, phone, birth_date, avatar_path, profile_text, created_at, updated_at)
         VALUES
            (:account_id, :nickname, :phone, :birth_date, :avatar_path, :profile_text, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE
            nickname = VALUES(nickname),
            phone = VALUES(phone),
            birth_date = VALUES(birth_date),
            avatar_path = VALUES(avatar_path),
            profile_text = VALUES(profile_text),
            updated_at = VALUES(updated_at)'
    );
    $stmt->execute([
        'account_id' => $accountId,
        'nickname' => trim((string) ($profile['nickname'] ?? '')),
        'phone' => trim((string) ($profile['phone'] ?? '')),
        'birth_date' => $birthDate,
        'avatar_path' => trim((string) ($profile['avatar_path'] ?? '')),
        'profile_text' => trim((string) ($profile['profile_text'] ?? '')),
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function toy_member_delete_profile(PDO $pdo, int $accountId): void
{
    $stmt = $pdo->prepare('DELETE FROM toy_member_profiles WHERE account_id = :account_id');
    $stmt->execute(['account_id' => $accountId]);
}

function toy_member_create_password_reset(PDO $pdo, array $config, int $accountId): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = toy_hmac_hash($token, $config);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $pdo->prepare(
        'INSERT INTO toy_member_password_resets (account_id, reset_token_hash, expires_at, created_at)
         VALUES (:account_id, :reset_token_hash, :expires_at, :created_at)'
    );
    $stmt->execute([
        'account_id' => $accountId,
        'reset_token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
        'created_at' => toy_now(),
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

function toy_member_mark_password_reset_used(PDO $pdo, int $resetId): void
{
    $stmt = $pdo->prepare('UPDATE toy_member_password_resets SET used_at = :used_at WHERE id = :id');
    $stmt->execute([
        'used_at' => toy_now(),
        'id' => $resetId,
    ]);
}

function toy_member_create_email_verification(PDO $pdo, array $config, int $accountId, string $email): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = toy_hmac_hash($token, $config);
    $expiresAt = date('Y-m-d H:i:s', time() + 86400);

    $stmt = $pdo->prepare(
        'INSERT INTO toy_member_email_verifications (account_id, email, verification_token_hash, expires_at, created_at)
         VALUES (:account_id, :email, :verification_token_hash, :expires_at, :created_at)'
    );
    $stmt->execute([
        'account_id' => $accountId,
        'email' => toy_normalize_identifier($email),
        'verification_token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
        'created_at' => toy_now(),
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

function toy_member_mark_email_verified(PDO $pdo, int $verificationId, int $accountId): void
{
    $now = toy_now();
    $stmt = $pdo->prepare('UPDATE toy_member_email_verifications SET verified_at = :verified_at WHERE id = :id');
    $stmt->execute([
        'verified_at' => $now,
        'id' => $verificationId,
    ]);

    $stmt = $pdo->prepare('UPDATE toy_member_accounts SET email_verified_at = :email_verified_at, updated_at = :updated_at WHERE id = :id');
    $stmt->execute([
        'email_verified_at' => $now,
        'updated_at' => $now,
        'id' => $accountId,
    ]);
}

function toy_member_record_consent(PDO $pdo, int $accountId, string $consentKey, string $version, bool $consented): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO toy_member_consents (account_id, consent_key, consent_version, consented, ip_address, user_agent, created_at)
         VALUES (:account_id, :consent_key, :consent_version, :consented, :ip_address, :user_agent, :created_at)'
    );
    $stmt->execute([
        'account_id' => $accountId,
        'consent_key' => $consentKey,
        'consent_version' => $version,
        'consented' => $consented ? 1 : 0,
        'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'created_at' => toy_now(),
    ]);
}

function toy_member_latest_consents(PDO $pdo, int $accountId): array
{
    $stmt = $pdo->prepare(
        'SELECT c.id, c.account_id, c.consent_key, c.consent_version, c.consented, c.ip_address, c.user_agent, c.created_at
         FROM toy_member_consents c
         INNER JOIN (
            SELECT consent_key, MAX(id) AS max_id
            FROM toy_member_consents
            WHERE account_id = :account_id
            GROUP BY consent_key
         ) latest ON latest.max_id = c.id
         ORDER BY c.consent_key ASC'
    );
    $stmt->execute(['account_id' => $accountId]);

    return $stmt->fetchAll();
}

function toy_member_privacy_export_data(PDO $pdo, int $accountId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, email, display_name, locale, status, email_verified_at, last_login_at, created_at, updated_at
         FROM toy_member_accounts
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $accountId]);
    $account = $stmt->fetch();

    if (!is_array($account)) {
        throw new RuntimeException('Account not found.');
    }

    $profile = toy_member_profile($pdo, $accountId);

    $stmt = $pdo->prepare(
        'SELECT consent_key, consent_version, consented, created_at
         FROM toy_member_consents
         WHERE account_id = :account_id
         ORDER BY id ASC'
    );
    $stmt->execute(['account_id' => $accountId]);
    $consents = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT event_type, result, ip_address, user_agent, created_at
         FROM toy_member_auth_logs
         WHERE account_id = :account_id
         ORDER BY id DESC
         LIMIT 100'
    );
    $stmt->execute(['account_id' => $accountId]);
    $authLogs = $stmt->fetchAll();

    $sessions = [];
    if (toy_member_sessions_table_exists($pdo)) {
        $stmt = $pdo->prepare(
            'SELECT ip_address, user_agent, expires_at, revoked_at, created_at, last_seen_at
             FROM toy_member_sessions
             WHERE account_id = :account_id
             ORDER BY id DESC
             LIMIT 100'
        );
        $stmt->execute(['account_id' => $accountId]);
        $sessions = $stmt->fetchAll();
    }

    $stmt = $pdo->prepare(
        'SELECT id, request_type, status, request_message, admin_note, handled_at, created_at, updated_at
         FROM toy_privacy_requests
         WHERE account_id = :account_id
         ORDER BY id ASC'
    );
    $stmt->execute(['account_id' => $accountId]);
    $privacyRequests = $stmt->fetchAll();

    return [
        'exported_at' => toy_now(),
        'account' => $account,
        'profile' => $profile,
        'consents' => $consents,
        'auth_logs' => $authLogs,
        'sessions' => $sessions,
        'privacy_requests' => $privacyRequests,
        'module_exports' => toy_member_module_privacy_exports($pdo, $accountId),
    ];
}

function toy_member_module_privacy_exports(PDO $pdo, int $accountId): array
{
    $exports = [];
    foreach (toy_enabled_module_keys($pdo) as $moduleKey) {
        if ($moduleKey === 'member' || preg_match('/\A[a-z0-9_]+\z/', $moduleKey) !== 1) {
            continue;
        }

        $exportFile = TOY_ROOT . '/modules/' . $moduleKey . '/privacy-export.php';
        if (!is_file($exportFile)) {
            continue;
        }

        $moduleExport = include $exportFile;
        if (is_callable($moduleExport)) {
            $exports[$moduleKey] = $moduleExport($pdo, $accountId);
        } elseif (is_array($moduleExport)) {
            $exports[$moduleKey] = $moduleExport;
        }
    }

    return $exports;
}

function toy_member_logout(?PDO $pdo = null): void
{
    if ($pdo instanceof PDO) {
        toy_member_revoke_current_session($pdo);
    }

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

function toy_member_login_throttle_status(PDO $pdo, ?int $accountId): array
{
    $windowSeconds = (int) toy_module_setting($pdo, 'member', 'login_throttle_window_seconds', 900);
    $accountLimit = (int) toy_module_setting($pdo, 'member', 'login_throttle_account_limit', 5);
    $ipLimit = (int) toy_module_setting($pdo, 'member', 'login_throttle_ip_limit', 20);

    $windowSeconds = min(86400, max(60, $windowSeconds));
    $accountLimit = min(100, max(1, $accountLimit));
    $ipLimit = min(500, max(1, $ipLimit));

    $windowStartedAt = date('Y-m-d H:i:s', time() - $windowSeconds);
    $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    if ($accountId !== null) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS failure_count
             FROM toy_member_auth_logs
             WHERE event_type IN (:login_event_type, :blocked_event_type)
               AND result = :result
               AND account_id = :account_id
               AND created_at >= :created_at'
        );
        $stmt->execute([
            'login_event_type' => 'login',
            'blocked_event_type' => 'login_blocked',
            'result' => 'failure',
            'account_id' => $accountId,
            'created_at' => $windowStartedAt,
        ]);
        $row = $stmt->fetch();
        if (is_array($row) && (int) $row['failure_count'] >= $accountLimit) {
            return ['limited' => true, 'reason' => 'account'];
        }
    }

    if ($ipAddress !== '') {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS failure_count
             FROM toy_member_auth_logs
             WHERE event_type IN (:login_event_type, :blocked_event_type)
               AND result = :result
               AND ip_address = :ip_address
               AND created_at >= :created_at'
        );
        $stmt->execute([
            'login_event_type' => 'login',
            'blocked_event_type' => 'login_blocked',
            'result' => 'failure',
            'ip_address' => $ipAddress,
            'created_at' => $windowStartedAt,
        ]);
        $row = $stmt->fetch();
        if (is_array($row) && (int) $row['failure_count'] >= $ipLimit) {
            return ['limited' => true, 'reason' => 'ip'];
        }
    }

    return ['limited' => false, 'reason' => ''];
}

function toy_member_password_reset_throttle_status(PDO $pdo, ?int $accountId): array
{
    $windowSeconds = (int) toy_module_setting($pdo, 'member', 'password_reset_throttle_window_seconds', 900);
    $accountLimit = (int) toy_module_setting($pdo, 'member', 'password_reset_throttle_account_limit', 3);
    $ipLimit = (int) toy_module_setting($pdo, 'member', 'password_reset_throttle_ip_limit', 10);

    $windowSeconds = min(86400, max(60, $windowSeconds));
    $accountLimit = min(50, max(1, $accountLimit));
    $ipLimit = min(200, max(1, $ipLimit));

    $windowStartedAt = date('Y-m-d H:i:s', time() - $windowSeconds);
    $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    if ($accountId !== null) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS request_count
             FROM toy_member_auth_logs
             WHERE event_type IN (:request_event_type, :blocked_event_type)
               AND account_id = :account_id
               AND created_at >= :created_at'
        );
        $stmt->execute([
            'request_event_type' => 'password_reset_request',
            'blocked_event_type' => 'password_reset_request_blocked',
            'account_id' => $accountId,
            'created_at' => $windowStartedAt,
        ]);
        $row = $stmt->fetch();
        if (is_array($row) && (int) $row['request_count'] >= $accountLimit) {
            return ['limited' => true, 'reason' => 'account'];
        }
    }

    if ($ipAddress !== '') {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS request_count
             FROM toy_member_auth_logs
             WHERE event_type IN (:request_event_type, :blocked_event_type)
               AND ip_address = :ip_address
               AND created_at >= :created_at'
        );
        $stmt->execute([
            'request_event_type' => 'password_reset_request',
            'blocked_event_type' => 'password_reset_request_blocked',
            'ip_address' => $ipAddress,
            'created_at' => $windowStartedAt,
        ]);
        $row = $stmt->fetch();
        if (is_array($row) && (int) $row['request_count'] >= $ipLimit) {
            return ['limited' => true, 'reason' => 'ip'];
        }
    }

    return ['limited' => false, 'reason' => ''];
}
