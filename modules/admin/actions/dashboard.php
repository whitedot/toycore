<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin', 'manager']);

$modules = [];
$stmt = $pdo->query('SELECT module_key, name, version, status FROM toy_modules ORDER BY id ASC');
foreach ($stmt->fetchAll() as $row) {
    $modules[] = $row;
}

$operationSummary = [];

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

include TOY_ROOT . '/modules/admin/views/dashboard.php';
