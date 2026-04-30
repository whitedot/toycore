<?php

declare(strict_types=1);

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
