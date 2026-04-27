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

include TOY_ROOT . '/modules/admin/views/dashboard.php';
