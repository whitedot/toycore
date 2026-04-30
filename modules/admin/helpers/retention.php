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
    return [
        'auth_logs_days' => (int) toy_post_string('auth_logs_days', 5),
        'audit_logs_days' => (int) toy_post_string('audit_logs_days', 5),
        'used_tokens_days' => (int) toy_post_string('used_tokens_days', 5),
        'sessions_days' => (int) toy_post_string('sessions_days', 5),
        'notifications_days' => (int) toy_post_string('notifications_days', 5),
        'module_backups_days' => (int) toy_post_string('module_backups_days', 5),
    ];
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
    return [
        'auth_logs' => toy_admin_retention_count(
            $pdo,
            'SELECT COUNT(*) AS count_value FROM toy_member_auth_logs WHERE created_at < :cutoff',
            ['cutoff' => $previewCutoffs['auth_logs']]
        ),
        'audit_logs' => toy_admin_retention_count(
            $pdo,
            'SELECT COUNT(*) AS count_value FROM toy_audit_logs WHERE created_at < :cutoff',
            ['cutoff' => $previewCutoffs['audit_logs']]
        ),
        'password_resets' => toy_admin_retention_count(
            $pdo,
            'SELECT COUNT(*) AS count_value FROM toy_member_password_resets WHERE used_at IS NOT NULL AND used_at < :cutoff',
            ['cutoff' => $previewCutoffs['used_tokens']]
        ),
        'email_verifications' => toy_admin_retention_count(
            $pdo,
            'SELECT COUNT(*) AS count_value FROM toy_member_email_verifications WHERE verified_at IS NOT NULL AND verified_at < :cutoff',
            ['cutoff' => $previewCutoffs['used_tokens']]
        ),
        'sessions' => toy_member_sessions_table_exists($pdo) ? toy_admin_retention_count(
            $pdo,
            'SELECT COUNT(*) AS count_value
             FROM toy_member_sessions
             WHERE (revoked_at IS NOT NULL AND revoked_at < :revoked_cutoff)
                OR expires_at < :expired_cutoff',
            [
                'revoked_cutoff' => $previewCutoffs['sessions'],
                'expired_cutoff' => $previewCutoffs['sessions'],
            ]
        ) : 0,
        'notifications' => $hasNotificationTables ? toy_admin_retention_count(
            $pdo,
            'SELECT COUNT(*) AS count_value FROM toy_notifications WHERE created_at < :cutoff',
            ['cutoff' => $previewCutoffs['notifications']]
        ) : 0,
        'notification_deliveries' => $hasNotificationTables ? toy_admin_retention_count(
            $pdo,
            'SELECT COUNT(*) AS count_value
             FROM toy_notification_deliveries d
             INNER JOIN toy_notifications n ON n.id = d.notification_id
             WHERE n.created_at < :cutoff',
            ['cutoff' => $previewCutoffs['notifications']]
        ) : 0,
        'notification_reads' => $hasNotificationTables ? toy_admin_retention_count(
            $pdo,
            'SELECT COUNT(*) AS count_value
             FROM toy_notification_reads r
             INNER JOIN toy_notifications n ON n.id = r.notification_id
             WHERE n.created_at < :cutoff',
            ['cutoff' => $previewCutoffs['notifications']]
        ) : 0,
        'module_backups' => toy_admin_retention_module_backup_count($previewCutoffs['module_backups']),
    ];
}

function toy_admin_retention_execute_cleanup(PDO $pdo, array $values, bool $hasNotificationTables): array
{
    $cutoffs = toy_admin_retention_preview_cutoffs($values);

    $deletedCounts = [
        'auth_logs' => toy_admin_retention_delete_count(
            $pdo,
            'DELETE FROM toy_member_auth_logs WHERE created_at < :cutoff',
            ['cutoff' => $cutoffs['auth_logs']]
        ),
        'audit_logs' => toy_admin_retention_delete_count(
            $pdo,
            'DELETE FROM toy_audit_logs WHERE created_at < :cutoff',
            ['cutoff' => $cutoffs['audit_logs']]
        ),
        'password_resets' => toy_admin_retention_delete_count(
            $pdo,
            'DELETE FROM toy_member_password_resets WHERE used_at IS NOT NULL AND used_at < :cutoff',
            ['cutoff' => $cutoffs['used_tokens']]
        ),
        'email_verifications' => toy_admin_retention_delete_count(
            $pdo,
            'DELETE FROM toy_member_email_verifications WHERE verified_at IS NOT NULL AND verified_at < :cutoff',
            ['cutoff' => $cutoffs['used_tokens']]
        ),
    ];

    if (toy_member_sessions_table_exists($pdo)) {
        $deletedCounts['sessions'] = toy_admin_retention_delete_count(
            $pdo,
            'DELETE FROM toy_member_sessions
             WHERE (revoked_at IS NOT NULL AND revoked_at < :revoked_cutoff)
                OR expires_at < :expired_cutoff',
            [
                'revoked_cutoff' => $cutoffs['sessions'],
                'expired_cutoff' => $cutoffs['sessions'],
            ]
        );
    }

    if ($hasNotificationTables) {
        $deletedCounts['notification_deliveries'] = toy_admin_retention_delete_count(
            $pdo,
            'DELETE d
             FROM toy_notification_deliveries d
             INNER JOIN toy_notifications n ON n.id = d.notification_id
             WHERE n.created_at < :cutoff',
            ['cutoff' => $cutoffs['notifications']]
        );
        $deletedCounts['notification_reads'] = toy_admin_retention_delete_count(
            $pdo,
            'DELETE r
             FROM toy_notification_reads r
             INNER JOIN toy_notifications n ON n.id = r.notification_id
             WHERE n.created_at < :cutoff',
            ['cutoff' => $cutoffs['notifications']]
        );
        $deletedCounts['notifications'] = toy_admin_retention_delete_count(
            $pdo,
            'DELETE FROM toy_notifications WHERE created_at < :cutoff',
            ['cutoff' => $cutoffs['notifications']]
        );
    }

    $deletedCounts['module_backups'] = toy_admin_retention_delete_module_backups($cutoffs['module_backups']);
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
