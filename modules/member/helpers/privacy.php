<?php

declare(strict_types=1);

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
        'ip_address' => toy_client_ip(),
        'user_agent' => toy_client_user_agent(),
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
