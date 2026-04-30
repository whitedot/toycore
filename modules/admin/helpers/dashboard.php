<?php

declare(strict_types=1);

function toy_admin_dashboard_modules(PDO $pdo): array
{
    $modules = [];
    $stmt = $pdo->query('SELECT module_key, name, version, status FROM toy_modules ORDER BY id ASC');
    foreach ($stmt->fetchAll() as $row) {
        $modules[] = $row;
    }

    return $modules;
}

function toy_admin_dashboard_table_exists(PDO $pdo, string $tableName): bool
{
    if (preg_match('/\Atoy_[a-z0-9_]{1,80}\z/', $tableName) !== 1) {
        return false;
    }

    try {
        $pdo->query('SELECT 1 FROM ' . $tableName . ' LIMIT 1');
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function toy_admin_dashboard_count(PDO $pdo, string $sql): int
{
    try {
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return is_array($row) ? (int) $row['count_value'] : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function toy_admin_dashboard_operation_summary(PDO $pdo): array
{
    $operationSummary = [];

    if (toy_admin_dashboard_table_exists($pdo, 'toy_site_menus') && toy_admin_dashboard_table_exists($pdo, 'toy_site_menu_items')) {
        $operationSummary[] = [
            'label' => '사이트 메뉴',
            'value' => (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_site_menus WHERE status = 'enabled'"),
            'detail' => '활성 메뉴 / 항목 ' . (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_site_menu_items WHERE status = 'enabled'"),
        ];
    }

    if (toy_admin_dashboard_table_exists($pdo, 'toy_banners')) {
        $operationSummary[] = [
            'label' => '배너',
            'value' => (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_banners WHERE status = 'enabled'"),
            'detail' => '활성 배너 / 임시저장 ' . (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_banners WHERE status = 'draft'"),
        ];
    }

    if (toy_admin_dashboard_table_exists($pdo, 'toy_notifications') && toy_admin_dashboard_table_exists($pdo, 'toy_notification_deliveries')) {
        $operationSummary[] = [
            'label' => '알림',
            'value' => (string) toy_admin_dashboard_count($pdo, 'SELECT COUNT(*) AS count_value FROM toy_notifications'),
            'detail' => '전체 알림 / 발송 대기 ' . (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_notification_deliveries WHERE status = 'queued'"),
        ];
    }

    return $operationSummary;
}

function toy_admin_dashboard_recovery_marker(string $filename, string $label): ?array
{
    if (preg_match('/\A[a-z0-9_.-]+\.json\z/', $filename) !== 1) {
        return null;
    }

    $path = TOY_ROOT . '/storage/' . $filename;
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }

    $content = file_get_contents($path);
    $decoded = is_string($content) ? json_decode($content, true) : null;
    if (!is_array($decoded)) {
        return [
            'label' => $label,
            'filename' => $filename,
            'recorded_at' => '',
            'stage' => '',
            'message' => '복구 marker를 읽을 수 없습니다.',
        ];
    }

    return [
        'label' => $label,
        'filename' => $filename,
        'recorded_at' => (string) ($decoded['recorded_at'] ?? ''),
        'stage' => (string) ($decoded['stage'] ?? ''),
        'scope' => (string) ($decoded['scope'] ?? ''),
        'module_key' => (string) ($decoded['module_key'] ?? ''),
        'version' => (string) ($decoded['version'] ?? ''),
        'message' => (string) ($decoded['message'] ?? ''),
    ];
}

function toy_admin_dashboard_recovery_markers(): array
{
    $recoveryMarkers = [];
    foreach ([
        'install-failed.json' => '설치 실패',
        'update-failed.json' => '업데이트 실패',
    ] as $filename => $label) {
        $marker = toy_admin_dashboard_recovery_marker($filename, $label);
        if (is_array($marker)) {
            $recoveryMarkers[] = $marker;
        }
    }

    return $recoveryMarkers;
}

function toy_admin_dashboard_module_backup_summary(): array
{
    $backupRoot = TOY_ROOT . '/storage/module-backups';
    $summary = [
        'count' => 0,
        'latest_name' => '',
        'latest_modified_at' => '',
    ];
    if (!is_dir($backupRoot)) {
        return $summary;
    }

    $directories = glob($backupRoot . '/*', GLOB_ONLYDIR);
    if (!is_array($directories)) {
        return $summary;
    }

    $latestTime = 0;
    foreach ($directories as $directory) {
        $summary['count']++;
        $modifiedAt = filemtime($directory);
        if ($modifiedAt !== false && $modifiedAt > $latestTime) {
            $latestTime = $modifiedAt;
            $summary['latest_name'] = basename($directory);
            $summary['latest_modified_at'] = date('Y-m-d H:i:s', $modifiedAt);
        }
    }

    return $summary;
}
