<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner']);

$values = toy_admin_retention_default_values();
$errors = [];
$notice = '';
$deletedCounts = [];

$hasNotificationTables = toy_admin_retention_notification_tables_exist($pdo);

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $postResult = toy_admin_handle_retention_post($pdo, $account, $hasNotificationTables);
    $errors = $postResult['errors'];
    $notice = (string) $postResult['notice'];
    $values = $postResult['values'];
    $deletedCounts = $postResult['deleted_counts'];
}

$previewCutoffs = toy_admin_retention_preview_cutoffs($values);
$previewCounts = toy_admin_retention_preview_counts($pdo, $previewCutoffs, $hasNotificationTables);

include TOY_ROOT . '/modules/admin/views/retention.php';
