<?php

declare(strict_types=1);

function toy_member_login_throttle_status(PDO $pdo, ?int $accountId): array
{
    $settings = toy_member_settings($pdo);
    $windowSeconds = (int) $settings['login_throttle_window_seconds'];
    $accountLimit = (int) $settings['login_throttle_account_limit'];
    $ipLimit = (int) $settings['login_throttle_ip_limit'];

    $windowSeconds = min(86400, max(60, $windowSeconds));
    $accountLimit = min(100, max(1, $accountLimit));
    $ipLimit = min(500, max(1, $ipLimit));

    $windowStartedAt = date('Y-m-d H:i:s', time() - $windowSeconds);
    $ipAddress = toy_client_ip();

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
    $settings = toy_member_settings($pdo);
    $windowSeconds = (int) $settings['password_reset_throttle_window_seconds'];
    $accountLimit = (int) $settings['password_reset_throttle_account_limit'];
    $ipLimit = (int) $settings['password_reset_throttle_ip_limit'];

    $windowSeconds = min(86400, max(60, $windowSeconds));
    $accountLimit = min(50, max(1, $accountLimit));
    $ipLimit = min(200, max(1, $ipLimit));

    $windowStartedAt = date('Y-m-d H:i:s', time() - $windowSeconds);
    $ipAddress = toy_client_ip();

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

function toy_member_email_verification_throttle_status(PDO $pdo, int $accountId): array
{
    $settings = toy_member_settings($pdo);
    $windowSeconds = (int) $settings['email_verification_throttle_window_seconds'];
    $accountLimit = (int) $settings['email_verification_throttle_account_limit'];
    $ipLimit = (int) $settings['email_verification_throttle_ip_limit'];

    $windowSeconds = min(86400, max(60, $windowSeconds));
    $accountLimit = min(50, max(1, $accountLimit));
    $ipLimit = min(200, max(1, $ipLimit));

    $windowStartedAt = date('Y-m-d H:i:s', time() - $windowSeconds);
    $ipAddress = toy_client_ip();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS request_count
         FROM toy_member_auth_logs
         WHERE event_type IN (:request_event_type, :blocked_event_type)
           AND account_id = :account_id
           AND created_at >= :created_at'
    );
    $stmt->execute([
        'request_event_type' => 'email_verification_request',
        'blocked_event_type' => 'email_verification_request_blocked',
        'account_id' => $accountId,
        'created_at' => $windowStartedAt,
    ]);
    $row = $stmt->fetch();
    if (is_array($row) && (int) $row['request_count'] >= $accountLimit) {
        return ['limited' => true, 'reason' => 'account'];
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
            'request_event_type' => 'email_verification_request',
            'blocked_event_type' => 'email_verification_request_blocked',
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

function toy_member_register_throttle_status(PDO $pdo): array
{
    $settings = toy_member_settings($pdo);
    $windowSeconds = (int) $settings['register_throttle_window_seconds'];
    $ipLimit = (int) $settings['register_throttle_ip_limit'];

    $windowSeconds = min(86400, max(60, $windowSeconds));
    $ipLimit = min(200, max(1, $ipLimit));

    $ipAddress = toy_client_ip();
    if ($ipAddress === '') {
        return ['limited' => false, 'reason' => ''];
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS request_count
         FROM toy_member_auth_logs
         WHERE event_type IN (:register_event_type, :blocked_event_type)
           AND ip_address = :ip_address
           AND created_at >= :created_at'
    );
    $stmt->execute([
        'register_event_type' => 'register',
        'blocked_event_type' => 'register_blocked',
        'ip_address' => $ipAddress,
        'created_at' => date('Y-m-d H:i:s', time() - $windowSeconds),
    ]);
    $row = $stmt->fetch();
    if (is_array($row) && (int) $row['request_count'] >= $ipLimit) {
        return ['limited' => true, 'reason' => 'ip'];
    }

    return ['limited' => false, 'reason' => ''];
}
