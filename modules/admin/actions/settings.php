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
$adminSettings = toy_admin_settings($pdo);
$adminSkinOptions = toy_admin_skin_options();
$adminSkinKey = toy_admin_skin_key($adminSettings);

if (toy_request_method() === 'POST' && toy_post_string('intent', 40) === 'admin_skin') {
    toy_require_csrf();
    $postedSkinKey = toy_post_string('admin_skin_key', 40);
    if (!isset($adminSkinOptions[$postedSkinKey])) {
        $errors[] = '관리자 스킨 값이 올바르지 않습니다.';
    }

    if ($errors === []) {
        toy_admin_save_skin_key($pdo, $postedSkinKey);
        $adminSettings = toy_admin_settings($pdo);
        $adminSkinKey = toy_admin_skin_key($adminSettings);
        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'admin',
            'event_type' => 'admin.settings.updated',
            'target_type' => 'module',
            'target_id' => 'admin',
            'result' => 'success',
            'message' => 'Admin settings updated.',
            'metadata' => [
                'admin_skin_key' => $adminSkinKey,
            ],
        ]);
        $notice = '관리자 설정을 저장했습니다.';
    }
} elseif (toy_request_method() === 'POST') {
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
