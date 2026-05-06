<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin', 'manager']);

$modules = toy_admin_dashboard_modules($pdo);
$operationSummary = toy_admin_dashboard_operation_summary($pdo);
$authRuntimeSummary = toy_admin_dashboard_auth_runtime_summary($pdo, $config);
$recoveryMarkers = toy_admin_dashboard_recovery_markers();
$moduleBackupSummary = toy_admin_dashboard_module_backup_summary();

include TOY_ROOT . '/modules/admin/views/dashboard.php';
