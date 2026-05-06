<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$errors = [];
$notice = '';
$settings = toy_member_settings($pdo);
$integerSettingKeys = toy_member_integer_setting_keys();

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $settings['allow_registration'] = ($_POST['allow_registration'] ?? '') === '1';
    $settings['email_verification_enabled'] = ($_POST['email_verification_enabled'] ?? '') === '1';
    $loginIdentifier = toy_post_string('login_identifier', 20);
    if (!in_array($loginIdentifier, ['email', 'login_id'], true)) {
        $errors[] = '로그인 식별자 설정이 올바르지 않습니다.';
    } else {
        $settings['login_identifier'] = $loginIdentifier;
    }
    foreach (toy_member_profile_field_setting_keys() as $key => $label) {
        $settings[$key] = ($_POST[$key] ?? '') === '1';
    }

    foreach ($integerSettingKeys as $key => $limits) {
        $integerValue = toy_admin_post_int_in_range($key, (int) $limits['min'], (int) $limits['max']);
        if ($integerValue === null) {
            $errors[] = $key . ' 값은 ' . (int) $limits['min'] . ' 이상 ' . (int) $limits['max'] . ' 이하의 정수여야 합니다.';
            continue;
        }

        $settings[$key] = $integerValue;
    }

    $stmt = $pdo->prepare("SELECT id FROM toy_modules WHERE module_key = 'member' LIMIT 1");
    $stmt->execute();
    $memberModule = $stmt->fetch();
    if (!is_array($memberModule)) {
        $errors[] = '회원 모듈이 등록되어 있지 않습니다.';
    }

    if ($errors === []) {
        $stmt = $pdo->prepare(
            'INSERT INTO toy_module_settings
                (module_id, setting_key, setting_value, value_type, created_at, updated_at)
             VALUES
                (:module_id, :setting_key, :setting_value, :value_type, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                value_type = VALUES(value_type),
                updated_at = VALUES(updated_at)'
        );

        $rows = [
            ['allow_registration', $settings['allow_registration'] ? '1' : '0', 'bool'],
            ['email_verification_enabled', $settings['email_verification_enabled'] ? '1' : '0', 'bool'],
            ['login_identifier', (string) $settings['login_identifier'], 'string'],
        ];
        foreach (toy_member_profile_field_setting_keys() as $key => $label) {
            $rows[] = [$key, !empty($settings[$key]) ? '1' : '0', 'bool'];
        }

        foreach ($integerSettingKeys as $key => $limits) {
            $rows[] = [$key, (string) $settings[$key], 'int'];
        }

        foreach ($rows as $row) {
            $stmt->execute([
                'module_id' => (int) $memberModule['id'],
                'setting_key' => $row[0],
                'setting_value' => $row[1],
                'value_type' => $row[2],
                'created_at' => toy_now(),
                'updated_at' => toy_now(),
            ]);
        }
        toy_clear_module_settings_cache('member');

        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'admin',
            'event_type' => 'member.settings.updated',
            'target_type' => 'module',
            'target_id' => 'member',
            'result' => 'success',
            'message' => 'Member settings updated.',
            'metadata' => [
                'allow_registration' => (bool) $settings['allow_registration'],
                'email_verification_enabled' => (bool) $settings['email_verification_enabled'],
                'login_identifier' => (string) $settings['login_identifier'],
                'profile_fields' => toy_member_profile_field_settings($settings),
            ],
        ]);

        $notice = '회원 설정을 저장했습니다.';
    }
}

include TOY_ROOT . '/modules/member/views/admin-settings.php';
