<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$errors = [];
$notice = '';
$allowedSettingTypes = ['string', 'int', 'bool', 'json'];
$values = [
    'name' => (string) ($site['name'] ?? ''),
    'base_url' => (string) ($site['base_url'] ?? ''),
    'timezone' => (string) ($site['timezone'] ?? 'Asia/Seoul'),
    'default_locale' => (string) ($site['default_locale'] ?? 'ko'),
    'status' => (string) ($site['status'] ?? 'active'),
];

if (toy_request_method() === 'POST') {
    toy_require_csrf();
    $intent = toy_post_string('intent', 40);

    if ($intent === 'site_setting') {
        $settingKey = toy_post_string('setting_key', 120);
        $settingValue = toy_post_string('setting_value', 5000);
        $valueType = toy_post_string('value_type', 20);
        $isPublic = ($_POST['is_public'] ?? '') === '1';

        if (preg_match('/\A[a-z][a-z0-9_.-]{1,119}\z/', $settingKey) !== 1) {
            $errors[] = '설정 key 형식이 올바르지 않습니다.';
        }

        if (!in_array($valueType, $allowedSettingTypes, true)) {
            $errors[] = '설정 타입이 올바르지 않습니다.';
        }

        if ($valueType === 'json' && json_decode($settingValue, true) === null && json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'JSON 설정값이 올바르지 않습니다.';
        }

        if ($errors === []) {
            $stmt = $pdo->prepare(
                'INSERT INTO toy_site_settings
                    (setting_key, setting_value, value_type, is_public, created_at, updated_at)
                 VALUES
                    (:setting_key, :setting_value, :value_type, :is_public, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    value_type = VALUES(value_type),
                    is_public = VALUES(is_public),
                    updated_at = VALUES(updated_at)'
            );
            $stmt->execute([
                'setting_key' => $settingKey,
                'setting_value' => $settingValue,
                'value_type' => $valueType,
                'is_public' => $isPublic ? 1 : 0,
                'created_at' => toy_now(),
                'updated_at' => toy_now(),
            ]);

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'site.setting.saved',
                'target_type' => 'site_setting',
                'target_id' => $settingKey,
                'result' => 'success',
                'message' => 'Site setting saved.',
                'metadata' => [
                    'value_type' => $valueType,
                    'is_public' => $isPublic,
                ],
            ]);

            $notice = '사이트 설정 항목을 저장했습니다.';
        }
    } elseif ($intent === 'delete_site_setting') {
        $settingKey = toy_post_string('setting_key', 120);
        if (preg_match('/\A[a-z][a-z0-9_.-]{1,119}\z/', $settingKey) !== 1) {
            $errors[] = '설정 key 형식이 올바르지 않습니다.';
        }

        if ($errors === []) {
            $stmt = $pdo->prepare('DELETE FROM toy_site_settings WHERE setting_key = :setting_key');
            $stmt->execute(['setting_key' => $settingKey]);

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'site.setting.deleted',
                'target_type' => 'site_setting',
                'target_id' => $settingKey,
                'result' => 'success',
                'message' => 'Site setting deleted.',
            ]);

            $notice = '사이트 설정 항목을 삭제했습니다.';
        }
    } else {
        $values = [
            'name' => toy_post_string('name', 120),
            'base_url' => toy_post_string('base_url', 255),
            'timezone' => toy_post_string('timezone', 80),
            'default_locale' => toy_post_string('default_locale', 20),
            'status' => toy_post_string('status', 30),
        ];

        if ($values['name'] === '') {
            $errors[] = '사이트 이름을 입력하세요.';
        }

        if ($values['base_url'] !== '' && filter_var($values['base_url'], FILTER_VALIDATE_URL) === false) {
            $errors[] = 'Base URL 형식이 올바르지 않습니다.';
        }

        if (!in_array($values['timezone'], timezone_identifiers_list(), true)) {
            $errors[] = 'timezone 값이 올바르지 않습니다.';
        }

        if (!preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/', $values['default_locale'])) {
            $errors[] = '기본 locale 값이 올바르지 않습니다.';
        }

        if (!in_array($values['status'], ['active', 'maintenance'], true)) {
            $errors[] = '운영 상태 값이 올바르지 않습니다.';
        }

        if ($errors === []) {
            $previousValues = [
                'name' => (string) ($site['name'] ?? ''),
                'base_url' => (string) ($site['base_url'] ?? ''),
                'timezone' => (string) ($site['timezone'] ?? ''),
                'default_locale' => (string) ($site['default_locale'] ?? ''),
                'status' => (string) ($site['status'] ?? ''),
            ];

            $stmt = $pdo->prepare(
                'UPDATE toy_sites
                 SET name = :name,
                     base_url = :base_url,
                     timezone = :timezone,
                     default_locale = :default_locale,
                     status = :status,
                     updated_at = :updated_at
                 WHERE id = :id'
            );
            $stmt->execute([
                'name' => $values['name'],
                'base_url' => $values['base_url'],
                'timezone' => $values['timezone'],
                'default_locale' => $values['default_locale'],
                'status' => $values['status'],
                'updated_at' => toy_now(),
                'id' => (int) ($site['id'] ?? 0),
            ]);

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'site.settings.updated',
                'target_type' => 'site',
                'target_id' => (string) ($site['id'] ?? ''),
                'result' => 'success',
                'message' => 'Site settings updated.',
                'metadata' => [
                    'before' => $previousValues,
                    'after' => $values,
                ],
            ]);

            $site = toy_load_site($pdo);
            if (is_array($site)) {
                $values = [
                    'name' => (string) ($site['name'] ?? ''),
                    'base_url' => (string) ($site['base_url'] ?? ''),
                    'timezone' => (string) ($site['timezone'] ?? 'Asia/Seoul'),
                    'default_locale' => (string) ($site['default_locale'] ?? 'ko'),
                    'status' => (string) ($site['status'] ?? 'active'),
                ];
            }

            $notice = '사이트 설정을 저장했습니다.';
        }
    }
}

$siteSettings = [];
$stmt = $pdo->query('SELECT setting_key, setting_value, value_type, is_public, updated_at FROM toy_site_settings ORDER BY setting_key ASC');
foreach ($stmt->fetchAll() as $row) {
    $siteSettings[] = $row;
}

include TOY_ROOT . '/modules/admin/views/settings.php';
