<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$filters = toy_admin_audit_log_filters();
$logs = toy_admin_audit_logs($pdo, $filters);

include TOY_ROOT . '/modules/admin/views/audit-logs.php';
