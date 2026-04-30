<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner']);

$defaults = [
    'auth_logs_days' => 180,
    'audit_logs_days' => 365,
    'used_tokens_days' => 30,
    'sessions_days' => 30,
    'notifications_days' => 365,
    'module_backups_days' => 180,
];
$values = $defaults;
$errors = [];
$notice = '';
$deletedCounts = [];

function toy_admin_retention_cutoff(int $days): string
{
    return date('Y-m-d H:i:s', time() - ($days * 86400));
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

$hasNotificationTables = toy_admin_retention_notification_tables_exist($pdo);

if (toy_request_method() === 'POST') {
    toy_require_csrf();
    $cleanupConfirmed = ($_POST['cleanup_confirmed'] ?? '') === '1';
    $cleanupPhrase = toy_post_string('cleanup_phrase', 20);

    $values = [
        'auth_logs_days' => (int) toy_post_string('auth_logs_days', 5),
        'audit_logs_days' => (int) toy_post_string('audit_logs_days', 5),
        'used_tokens_days' => (int) toy_post_string('used_tokens_days', 5),
        'sessions_days' => (int) toy_post_string('sessions_days', 5),
        'notifications_days' => (int) toy_post_string('notifications_days', 5),
        'module_backups_days' => (int) toy_post_string('module_backups_days', 5),
    ];

    foreach ($values as $key => $days) {
        if ($days < 1 || $days > 3650) {
            $errors[] = '보관 기간은 1일부터 3650일 사이로 입력하세요.';
            break;
        }
    }

    if (!$cleanupConfirmed || $cleanupPhrase !== 'DELETE') {
        $errors[] = '정리 실행 전 확인 체크와 DELETE 입력이 필요합니다.';
    }

    if ($errors === []) {
        $authCutoff = toy_admin_retention_cutoff($values['auth_logs_days']);
        $auditCutoff = toy_admin_retention_cutoff($values['audit_logs_days']);
        $tokenCutoff = toy_admin_retention_cutoff($values['used_tokens_days']);
        $sessionCutoff = toy_admin_retention_cutoff($values['sessions_days']);
        $notificationCutoff = toy_admin_retention_cutoff($values['notifications_days']);
        $moduleBackupCutoff = toy_admin_retention_cutoff($values['module_backups_days']);

        $stmt = $pdo->prepare('DELETE FROM toy_member_auth_logs WHERE created_at < :cutoff');
        $stmt->execute(['cutoff' => $authCutoff]);
        $deletedCounts['auth_logs'] = $stmt->rowCount();

        $stmt = $pdo->prepare('DELETE FROM toy_audit_logs WHERE created_at < :cutoff');
        $stmt->execute(['cutoff' => $auditCutoff]);
        $deletedCounts['audit_logs'] = $stmt->rowCount();

        $stmt = $pdo->prepare('DELETE FROM toy_member_password_resets WHERE used_at IS NOT NULL AND used_at < :cutoff');
        $stmt->execute(['cutoff' => $tokenCutoff]);
        $deletedCounts['password_resets'] = $stmt->rowCount();

        $stmt = $pdo->prepare('DELETE FROM toy_member_email_verifications WHERE verified_at IS NOT NULL AND verified_at < :cutoff');
        $stmt->execute(['cutoff' => $tokenCutoff]);
        $deletedCounts['email_verifications'] = $stmt->rowCount();

        if (toy_member_sessions_table_exists($pdo)) {
            $stmt = $pdo->prepare(
                'DELETE FROM toy_member_sessions
                 WHERE (revoked_at IS NOT NULL AND revoked_at < :revoked_cutoff)
                    OR expires_at < :expired_cutoff'
            );
            $stmt->execute([
                'revoked_cutoff' => $sessionCutoff,
                'expired_cutoff' => $sessionCutoff,
            ]);
            $deletedCounts['sessions'] = $stmt->rowCount();
        }

        if ($hasNotificationTables) {
            $stmt = $pdo->prepare(
                'DELETE d
                 FROM toy_notification_deliveries d
                 INNER JOIN toy_notifications n ON n.id = d.notification_id
                 WHERE n.created_at < :cutoff'
            );
            $stmt->execute(['cutoff' => $notificationCutoff]);
            $deletedCounts['notification_deliveries'] = $stmt->rowCount();

            $stmt = $pdo->prepare(
                'DELETE r
                 FROM toy_notification_reads r
                 INNER JOIN toy_notifications n ON n.id = r.notification_id
                 WHERE n.created_at < :cutoff'
            );
            $stmt->execute(['cutoff' => $notificationCutoff]);
            $deletedCounts['notification_reads'] = $stmt->rowCount();

            $stmt = $pdo->prepare('DELETE FROM toy_notifications WHERE created_at < :cutoff');
            $stmt->execute(['cutoff' => $notificationCutoff]);
            $deletedCounts['notifications'] = $stmt->rowCount();
        }

        $deletedCounts['module_backups'] = toy_admin_retention_delete_module_backups($moduleBackupCutoff);

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

        $notice = '보관 기간 정리를 실행했습니다.';
    }
}

$previewCutoffs = [
    'auth_logs' => toy_admin_retention_cutoff($values['auth_logs_days']),
    'audit_logs' => toy_admin_retention_cutoff($values['audit_logs_days']),
    'used_tokens' => toy_admin_retention_cutoff($values['used_tokens_days']),
    'sessions' => toy_admin_retention_cutoff($values['sessions_days']),
    'notifications' => toy_admin_retention_cutoff($values['notifications_days']),
    'module_backups' => toy_admin_retention_cutoff($values['module_backups_days']),
];

$previewCounts = [
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

include TOY_ROOT . '/modules/admin/views/retention.php';
