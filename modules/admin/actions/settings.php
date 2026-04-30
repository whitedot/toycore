<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);
$canManageAdvancedSettings = toy_admin_has_role($pdo, (int) $account['id'], ['owner']);

$errors = [];
$notice = '';
$allowedSettingTypes = toy_admin_settings_allowed_types();
$reservedSiteSettingKeys = toy_admin_reserved_site_setting_keys();
$values = toy_admin_site_setting_values($site ?? null);

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $postResult = toy_admin_handle_settings_post(
        $pdo,
        $account,
        $site ?? null,
        $canManageAdvancedSettings,
        $allowedSettingTypes,
        $reservedSiteSettingKeys
    );
    $errors = $postResult['errors'];
    $notice = (string) $postResult['notice'];
    $values = $postResult['values'];
    $site = is_array($postResult['site']) ? $postResult['site'] : ($site ?? null);
}

$siteSettings = toy_admin_site_settings($pdo);

include TOY_ROOT . '/modules/admin/views/settings.php';
