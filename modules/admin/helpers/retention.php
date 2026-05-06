<?php

declare(strict_types=1);

function toy_admin_retention_cutoff(int $days): string
{
    return date('Y-m-d H:i:s', time() - ($days * 86400));
}

function toy_admin_retention_default_values(): array
{
    return [
        'auth_logs_days' => 180,
        'audit_logs_days' => 365,
        'used_tokens_days' => 30,
        'sessions_days' => 30,
        'notifications_days' => 365,
        'module_backups_days' => 180,
    ];
}

function toy_admin_retention_post_values(): array
{
    $values = [];
    foreach (array_keys(toy_admin_retention_default_values()) as $key) {
        $values[$key] = toy_admin_post_int_in_range($key, 1, 3650, 5) ?? 0;
    }

    return $values;
}

function toy_admin_validate_retention_cleanup(array $values): array
{
    $errors = [];
    foreach ($values as $days) {
        if ($days < 1 || $days > 3650) {
            $errors[] = '보관 기간은 1일부터 3650일 사이로 입력하세요.';
            break;
        }
    }

    $cleanupConfirmed = ($_POST['cleanup_confirmed'] ?? '') === '1';
    $cleanupPhrase = toy_post_string('cleanup_phrase', 20);
    if (!$cleanupConfirmed || $cleanupPhrase !== 'DELETE') {
        $errors[] = '정리 실행 전 확인 체크와 DELETE 입력이 필요합니다.';
    }

    return $errors;
}

function toy_admin_retention_count(PDO $pdo, string $sql, array $params): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return is_array($row) ? (int) $row['count_value'] : 0;
}

function toy_admin_retention_notification_tables_exist(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM toy_notifications LIMIT 1');
        $pdo->query('SELECT 1 FROM toy_notification_deliveries LIMIT 1');
        $pdo->query('SELECT 1 FROM toy_notification_reads LIMIT 1');
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function toy_admin_retention_runtime_sessions_table_exists(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM toy_sessions LIMIT 1');
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function toy_admin_retention_rate_limits_table_exists(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM toy_rate_limits LIMIT 1');
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function toy_admin_retention_target_definitions(bool $hasNotificationTables, bool $hasSessionsTable, bool $hasRuntimeSessionsTable = false, bool $hasRateLimitsTable = false): array
{
    return [
        'auth_logs' => [
            'enabled' => true,
            'cutoff_key' => 'auth_logs',
            'count_sql' => 'SELECT COUNT(*) AS count_value FROM toy_member_auth_logs WHERE created_at < :cutoff',
            'count_params' => [
                'cutoff' => 'auth_logs',
            ],
            'delete_sql' => 'DELETE FROM toy_member_auth_logs WHERE created_at < :cutoff',
            'delete_params' => [
                'cutoff' => 'auth_logs',
            ],
        ],
        'audit_logs' => [
            'enabled' => true,
            'cutoff_key' => 'audit_logs',
            'count_sql' => 'SELECT COUNT(*) AS count_value FROM toy_audit_logs WHERE created_at < :cutoff',
            'count_params' => [
                'cutoff' => 'audit_logs',
            ],
            'delete_sql' => 'DELETE FROM toy_audit_logs WHERE created_at < :cutoff',
            'delete_params' => [
                'cutoff' => 'audit_logs',
            ],
        ],
        'password_resets' => [
            'enabled' => true,
            'cutoff_key' => 'used_tokens',
            'count_sql' => 'SELECT COUNT(*) AS count_value FROM toy_member_password_resets WHERE used_at IS NOT NULL AND used_at < :cutoff',
            'count_params' => [
                'cutoff' => 'used_tokens',
            ],
            'delete_sql' => 'DELETE FROM toy_member_password_resets WHERE used_at IS NOT NULL AND used_at < :cutoff',
            'delete_params' => [
                'cutoff' => 'used_tokens',
            ],
        ],
        'email_verifications' => [
            'enabled' => true,
            'cutoff_key' => 'used_tokens',
            'count_sql' => 'SELECT COUNT(*) AS count_value FROM toy_member_email_verifications WHERE verified_at IS NOT NULL AND verified_at < :cutoff',
            'count_params' => [
                'cutoff' => 'used_tokens',
            ],
            'delete_sql' => 'DELETE FROM toy_member_email_verifications WHERE verified_at IS NOT NULL AND verified_at < :cutoff',
            'delete_params' => [
                'cutoff' => 'used_tokens',
            ],
        ],
        'sessions' => [
            'enabled' => $hasSessionsTable,
            'cutoff_key' => 'sessions',
            'count_sql' => 'SELECT COUNT(*) AS count_value
             FROM toy_member_sessions
             WHERE (revoked_at IS NOT NULL AND revoked_at < :revoked_cutoff)
                OR expires_at < :expired_cutoff',
            'count_params' => [
                'revoked_cutoff' => 'sessions',
                'expired_cutoff' => 'sessions',
            ],
            'delete_sql' => 'DELETE FROM toy_member_sessions
             WHERE (revoked_at IS NOT NULL AND revoked_at < :revoked_cutoff)
                OR expires_at < :expired_cutoff',
            'delete_params' => [
                'revoked_cutoff' => 'sessions',
                'expired_cutoff' => 'sessions',
            ],
        ],
        'runtime_sessions' => [
            'enabled' => $hasRuntimeSessionsTable,
            'cutoff_key' => 'sessions',
            'count_sql' => 'SELECT COUNT(*) AS count_value
             FROM toy_sessions
             WHERE expires_at < :expired_cutoff',
            'count_params' => [
                'expired_cutoff' => 'sessions',
            ],
            'delete_sql' => 'DELETE FROM toy_sessions
             WHERE expires_at < :expired_cutoff',
            'delete_params' => [
                'expired_cutoff' => 'sessions',
            ],
        ],
        'rate_limits' => [
            'enabled' => $hasRateLimitsTable,
            'cutoff_key' => 'sessions',
            'count_sql' => 'SELECT COUNT(*) AS count_value
             FROM toy_rate_limits
             WHERE expires_at < :expired_cutoff',
            'count_params' => [
                'expired_cutoff' => 'sessions',
            ],
            'delete_sql' => 'DELETE FROM toy_rate_limits
             WHERE expires_at < :expired_cutoff',
            'delete_params' => [
                'expired_cutoff' => 'sessions',
            ],
        ],
        'notifications' => [
            'enabled' => $hasNotificationTables,
            'cutoff_key' => 'notifications',
            'count_sql' => 'SELECT COUNT(*) AS count_value FROM toy_notifications WHERE created_at < :cutoff',
            'count_params' => [
                'cutoff' => 'notifications',
            ],
            'delete_sql' => 'DELETE FROM toy_notifications WHERE created_at < :cutoff',
            'delete_params' => [
                'cutoff' => 'notifications',
            ],
        ],
        'notification_deliveries' => [
            'enabled' => $hasNotificationTables,
            'cutoff_key' => 'notifications',
            'count_sql' => 'SELECT COUNT(*) AS count_value
             FROM toy_notification_deliveries d
             INNER JOIN toy_notifications n ON n.id = d.notification_id
             WHERE n.created_at < :cutoff',
            'count_params' => [
                'cutoff' => 'notifications',
            ],
            'delete_sql' => 'DELETE d
             FROM toy_notification_deliveries d
             INNER JOIN toy_notifications n ON n.id = d.notification_id
             WHERE n.created_at < :cutoff',
            'delete_params' => [
                'cutoff' => 'notifications',
            ],
        ],
        'notification_reads' => [
            'enabled' => $hasNotificationTables,
            'cutoff_key' => 'notifications',
            'count_sql' => 'SELECT COUNT(*) AS count_value
             FROM toy_notification_reads r
             INNER JOIN toy_notifications n ON n.id = r.notification_id
             WHERE n.created_at < :cutoff',
            'count_params' => [
                'cutoff' => 'notifications',
            ],
            'delete_sql' => 'DELETE r
             FROM toy_notification_reads r
             INNER JOIN toy_notifications n ON n.id = r.notification_id
             WHERE n.created_at < :cutoff',
            'delete_params' => [
                'cutoff' => 'notifications',
            ],
        ],
        'module_backups' => [
            'enabled' => true,
            'cutoff_key' => 'module_backups',
            'count_callback' => 'toy_admin_retention_module_backup_count',
            'delete_callback' => 'toy_admin_retention_delete_module_backups',
        ],
    ];
}

function toy_admin_retention_cleanup_target_keys(): array
{
    return [
        'auth_logs',
        'audit_logs',
        'password_resets',
        'email_verifications',
        'sessions',
        'runtime_sessions',
        'rate_limits',
        'notification_deliveries',
        'notification_reads',
        'notifications',
        'module_backups',
    ];
}

function toy_admin_retention_query_params(array $paramCutoffKeys, array $cutoffs): array
{
    $params = [];
    foreach ($paramCutoffKeys as $paramName => $cutoffKey) {
        $params[$paramName] = $cutoffs[$cutoffKey];
    }

    return $params;
}

function toy_admin_retention_module_backup_dirs(string $cutoff): array
{
    $backupRoot = TOY_ROOT . '/storage/module-backups';
    if (!is_dir($backupRoot)) {
        return [];
    }

    $cutoffTime = strtotime($cutoff);
    if ($cutoffTime === false) {
        return [];
    }

    $directories = glob($backupRoot . '/*', GLOB_ONLYDIR);
    if (!is_array($directories)) {
        return [];
    }

    $oldDirectories = [];
    foreach ($directories as $directory) {
        $modifiedAt = filemtime($directory);
        if ($modifiedAt !== false && $modifiedAt < $cutoffTime) {
            $oldDirectories[] = $directory;
        }
    }

    sort($oldDirectories, SORT_STRING);
    return $oldDirectories;
}

function toy_admin_retention_module_backup_count(string $cutoff): int
{
    return count(toy_admin_retention_module_backup_dirs($cutoff));
}

function toy_admin_retention_delete_module_backups(string $cutoff): int
{
    $deletedCount = 0;
    foreach (toy_admin_retention_module_backup_dirs($cutoff) as $directory) {
        toy_admin_remove_directory($directory);
        $deletedCount++;
    }

    return $deletedCount;
}

function toy_admin_retention_delete_count(PDO $pdo, string $sql, array $params): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function toy_admin_retention_delete_target(PDO $pdo, array $target, array $cutoffs): int
{
    $deleteCallback = (string) ($target['delete_callback'] ?? '');
    if ($deleteCallback !== '') {
        return $deleteCallback($cutoffs[$target['cutoff_key']]);
    }

    return toy_admin_retention_delete_count(
        $pdo,
        (string) $target['delete_sql'],
        toy_admin_retention_query_params($target['delete_params'], $cutoffs)
    );
}

function toy_admin_retention_preview_cutoffs(array $values): array
{
    return [
        'auth_logs' => toy_admin_retention_cutoff($values['auth_logs_days']),
        'audit_logs' => toy_admin_retention_cutoff($values['audit_logs_days']),
        'used_tokens' => toy_admin_retention_cutoff($values['used_tokens_days']),
        'sessions' => toy_admin_retention_cutoff($values['sessions_days']),
        'notifications' => toy_admin_retention_cutoff($values['notifications_days']),
        'module_backups' => toy_admin_retention_cutoff($values['module_backups_days']),
    ];
}

function toy_admin_retention_preview_counts(PDO $pdo, array $previewCutoffs, bool $hasNotificationTables): array
{
    $previewCounts = [];
    $targets = toy_admin_retention_target_definitions(
        $hasNotificationTables,
        toy_member_sessions_table_exists($pdo),
        toy_admin_retention_runtime_sessions_table_exists($pdo),
        toy_admin_retention_rate_limits_table_exists($pdo)
    );

    foreach ($targets as $key => $target) {
        if (!$target['enabled']) {
            $previewCounts[$key] = 0;
            continue;
        }

        $countCallback = (string) ($target['count_callback'] ?? '');
        if ($countCallback !== '') {
            $previewCounts[$key] = $countCallback($previewCutoffs[$target['cutoff_key']]);
            continue;
        }

        $previewCounts[$key] = toy_admin_retention_count(
            $pdo,
            (string) $target['count_sql'],
            toy_admin_retention_query_params($target['count_params'], $previewCutoffs)
        );
    }

    return $previewCounts;
}

function toy_admin_retention_execute_cleanup(PDO $pdo, array $values, bool $hasNotificationTables): array
{
    $cutoffs = toy_admin_retention_preview_cutoffs($values);
    $targets = toy_admin_retention_target_definitions(
        $hasNotificationTables,
        toy_member_sessions_table_exists($pdo),
        toy_admin_retention_runtime_sessions_table_exists($pdo),
        toy_admin_retention_rate_limits_table_exists($pdo)
    );
    $deletedCounts = [];
    foreach (toy_admin_retention_cleanup_target_keys() as $key) {
        $target = $targets[$key];
        if (!$target['enabled']) {
            continue;
        }

        $deletedCounts[$key] = toy_admin_retention_delete_target($pdo, $target, $cutoffs);
    }

    return $deletedCounts;
}

function toy_admin_log_retention_cleanup(PDO $pdo, array $account, array $values, array $deletedCounts): void
{
    toy_audit_log($pdo, [
        'actor_account_id' => (int) $account['id'],
        'actor_type' => 'admin',
        'event_type' => 'retention.cleanup.completed',
        'target_type' => 'retention',
        'target_id' => 'manual',
        'result' => 'success',
        'message' => 'Retention cleanup completed.',
        'metadata' => [
            'days' => $values,
            'deleted' => $deletedCounts,
        ],
    ]);
}

function toy_admin_handle_retention_post(PDO $pdo, array $account, bool $hasNotificationTables): array
{
    $values = toy_admin_retention_post_values();
    $errors = toy_admin_validate_retention_cleanup($values);
    $deletedCounts = [];
    $notice = '';

    if ($errors === []) {
        $deletedCounts = toy_admin_retention_execute_cleanup($pdo, $values, $hasNotificationTables);
        toy_admin_log_retention_cleanup($pdo, $account, $values, $deletedCounts);
        $notice = '보관 기간 정리를 실행했습니다.';
    }

    return array_merge(toy_admin_action_result($errors, $notice), [
        'values' => $values,
        'deleted_counts' => $deletedCounts,
    ]);
}
