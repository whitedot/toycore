<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner']);

$allowedRoles = toy_admin_allowed_roles();
$allowedActions = toy_admin_role_actions();
$errors = [];
$notice = '';

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $postResult = toy_admin_handle_roles_post($pdo, $account, $allowedRoles, $allowedActions);
    $errors = $postResult['errors'];
    $notice = (string) $postResult['notice'];
}

$accounts = toy_admin_role_accounts($pdo);

include TOY_ROOT . '/modules/admin/views/roles.php';
