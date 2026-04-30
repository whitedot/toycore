<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner']);

$errors = [];
$notice = '';
$appliedUpdates = [];
$previousUpdateFailure = toy_admin_previous_update_failure();

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $postResult = toy_admin_handle_updates_post($pdo, $account);
    $errors = $postResult['errors'];
    $notice = (string) $postResult['notice'];
    $appliedUpdates = $postResult['applied_updates'];
}

$pendingUpdates = toy_admin_pending_updates($pdo);
$schemaVersions = toy_admin_schema_versions($pdo);
$pendingUpdateCounts = toy_admin_module_pending_update_counts($pendingUpdates);
$moduleVersionDrifts = toy_admin_module_version_drifts($pdo, $pendingUpdateCounts);

include TOY_ROOT . '/modules/admin/views/updates.php';
