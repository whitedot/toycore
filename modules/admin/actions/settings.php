<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);
$canManageAdvancedSettings = toy_admin_has_role($pdo, (int) $account['id'], ['owner']);

$errors = [];
$notice = '';
$allowedSettingTypes = ['string', 'int', 'bool', 'json'];
$reservedSiteSettingKeys = [
    'site.name' => true,
    'site.base_url' => true,
    'site.timezone' => true,
    'site.default_locale' => true,
    'site.supported_locales' => true,
    'site.status' => true,
];
$values = [
    'name' => (string) ($site['name'] ?? ''),
    'base_url' => (string) ($site['base_url'] ?? ''),
    'timezone' => (string) ($site['timezone'] ?? 'Asia/Seoul'),
    'default_locale' => (string) ($site['default_locale'] ?? 'ko'),
    'supported_locales' => (string) ($site['supported_locales'] ?? (string) ($site['default_locale'] ?? 'ko')),
    'status' => (string) ($site['status'] ?? 'active'),
];

if (toy_request_method() === 'POST') {
    toy_require_csrf();
    $intent = toy_post_string('intent', 40);

    if ($intent === 'site_setting') {
        if (!$canManageAdvancedSettings) {
            $errors[] = '고급 사이트 설정은 owner 권한이 필요합니다.';
        }

        $settingKey = toy_post_string('setting_key', 120);
        $settingValue = toy_post_string('setting_value', 5000);
        $valueType = toy_post_string('value_type', 20);

        if (preg_match('/\A[a-z][a-z0-9_.-]{1,119}\z/', $settingKey) !== 1) {
            $errors[] = '설정 key 형식이 올바르지 않습니다.';
        }

        if (!in_array($valueType, $allowedSettingTypes, true)) {
            $errors[] = '설정 타입이 올바르지 않습니다.';
        }

        if (isset($reservedSiteSettingKeys[$settingKey])) {
            $errors[] = '기본 사이트 설정은 위의 전용 양식에서 수정하세요.';
        }

        if ($valueType === 'json' && json_decode($settingValue, true) === null && json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'JSON 설정값이 올바르지 않습니다.';
        }

        if ($errors === []) {
            toy_save_site_setting($pdo, $settingKey, $settingValue, $valueType);

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
                ],
            ]);

            $notice = '사이트 설정 항목을 저장했습니다.';
        }
    } elseif ($intent === 'delete_site_setting') {
        if (!$canManageAdvancedSettings) {
            $errors[] = '고급 사이트 설정은 owner 권한이 필요합니다.';
        }

        $settingKey = toy_post_string('setting_key', 120);
        if (preg_match('/\A[a-z][a-z0-9_.-]{1,119}\z/', $settingKey) !== 1) {
            $errors[] = '설정 key 형식이 올바르지 않습니다.';
        }

        if (isset($reservedSiteSettingKeys[$settingKey])) {
            $errors[] = '기본 사이트 설정은 삭제할 수 없습니다.';
        }

        if ($errors === []) {
            $stmt = $pdo->prepare('DELETE FROM toy_site_settings WHERE setting_key = :setting_key');
            $stmt->execute(['setting_key' => $settingKey]);
            toy_clear_site_settings_cache();

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
            'supported_locales' => toy_post_string('supported_locales', 255),
            'status' => toy_post_string('status', 30),
        ];

        if ($values['name'] === '') {
            $errors[] = '사이트 이름을 입력하세요.';
        }

        if ($values['base_url'] !== '' && !toy_is_http_url($values['base_url'])) {
            $errors[] = 'Base URL은 http 또는 https URL이어야 합니다.';
        }

        if (!in_array($values['timezone'], timezone_identifiers_list(), true)) {
            $errors[] = 'timezone 값이 올바르지 않습니다.';
        }

        if (!preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/', $values['default_locale'])) {
            $errors[] = '기본 locale 값이 올바르지 않습니다.';
        }

        $supportedLocales = [];
        foreach (preg_split('/[\s,]+/', $values['supported_locales']) ?: [] as $locale) {
            if ($locale === '') {
                continue;
            }

            if (preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/', $locale) !== 1) {
                $errors[] = '지원 locale 목록 값이 올바르지 않습니다.';
                break;
            }

            $supportedLocales[$locale] = $locale;
        }

        if ($errors === [] && !isset($supportedLocales[$values['default_locale']])) {
            $supportedLocales[$values['default_locale']] = $values['default_locale'];
        }

        if ($errors === []) {
            $values['supported_locales'] = implode(',', array_values($supportedLocales));
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
                'supported_locales' => (string) ($site['supported_locales'] ?? ''),
                'status' => (string) ($site['status'] ?? ''),
            ];

            toy_save_site_settings($pdo, [
                'site.name' => ['value' => $values['name'], 'type' => 'string'],
                'site.base_url' => ['value' => $values['base_url'], 'type' => 'string'],
                'site.timezone' => ['value' => $values['timezone'], 'type' => 'string'],
                'site.default_locale' => ['value' => $values['default_locale'], 'type' => 'string'],
                'site.supported_locales' => ['value' => $values['supported_locales'], 'type' => 'string'],
                'site.status' => ['value' => $values['status'], 'type' => 'string'],
            ]);

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'site.settings.updated',
                'target_type' => 'site_settings',
                'target_id' => 'site',
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
                    'supported_locales' => (string) ($site['supported_locales'] ?? (string) ($site['default_locale'] ?? 'ko')),
                    'status' => (string) ($site['status'] ?? 'active'),
                ];
            }

            $notice = '사이트 설정을 저장했습니다.';
        }
    }
}

$siteSettings = [];
$stmt = $pdo->query(
    "SELECT setting_key, setting_value, value_type, updated_at
     FROM toy_site_settings
     WHERE setting_key NOT IN ('site.name', 'site.base_url', 'site.timezone', 'site.default_locale', 'site.supported_locales', 'site.status')
     ORDER BY setting_key ASC"
);
foreach ($stmt->fetchAll() as $row) {
    $siteSettings[] = $row;
}

include TOY_ROOT . '/modules/admin/views/settings.php';
