<?php

declare(strict_types=1);

$errors = [];
$requiredModules = [
    'member' => ['name' => 'Member', 'version' => '2026.04.006'],
    'admin' => ['name' => 'Admin', 'version' => '2026.04.001'],
];
$optionalModules = [
    'seo' => ['name' => 'SEO', 'version' => '2026.04.002', 'label' => 'SEO'],
    'popup_layer' => ['name' => 'Popup Layer', 'version' => '2026.04.001', 'label' => '팝업레이어'],
    'point' => ['name' => 'Point', 'version' => '2026.04.001', 'label' => '포인트'],
    'deposit' => ['name' => 'Deposit', 'version' => '2026.04.001', 'label' => '예치금'],
    'reward' => ['name' => 'Reward', 'version' => '2026.04.001', 'label' => '적립금'],
];
$selectedOptionalModuleKeys = array_keys($optionalModules);
$values = [
    'db_host' => 'localhost',
    'db_name' => '',
    'db_user' => '',
    'db_password' => '',
    'site_name' => 'Toycore',
    'base_url' => '',
    'timezone' => 'Asia/Seoul',
    'default_locale' => 'ko',
    'admin_email' => '',
    'admin_display_name' => '관리자',
];

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    foreach ($values as $key => $default) {
        $values[$key] = toy_post_string($key, $key === 'db_password' ? 255 : 120);
    }

    $postedOptionalModules = $_POST['optional_modules'] ?? [];
    $selectedOptionalModuleKeys = [];
    if (!is_array($postedOptionalModules)) {
        $errors[] = '선택 모듈 값이 올바르지 않습니다.';
    } else {
        foreach ($postedOptionalModules as $moduleKey) {
            $moduleKey = is_string($moduleKey) ? $moduleKey : '';
            if (!array_key_exists($moduleKey, $optionalModules)) {
                $errors[] = '선택할 수 없는 모듈이 포함되어 있습니다.';
                continue;
            }

            $selectedOptionalModuleKeys[$moduleKey] = $moduleKey;
        }

        $selectedOptionalModuleKeys = array_values($selectedOptionalModuleKeys);
    }

    $adminPassword = toy_post_string('admin_password', 255);
    $adminPasswordConfirm = toy_post_string('admin_password_confirm', 255);

    if ($values['db_name'] === '' || $values['db_user'] === '') {
        $errors[] = 'DB 이름과 DB 사용자를 입력하세요.';
    }

    if (!filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = '관리자 이메일 형식이 올바르지 않습니다.';
    }

    if (!in_array($values['timezone'], timezone_identifiers_list(), true)) {
        $errors[] = 'timezone 값이 올바르지 않습니다.';
    }

    if ($values['base_url'] !== '') {
        if (!toy_is_http_url($values['base_url'])) {
            $errors[] = 'Base URL은 http 또는 https URL이어야 합니다.';
        } elseif (!toy_is_local_host($values['base_url']) && parse_url($values['base_url'], PHP_URL_SCHEME) !== 'https') {
            $errors[] = '운영 Base URL은 HTTPS URL이어야 합니다.';
        }
    }

    if (strlen($adminPassword) < 8) {
        $errors[] = '관리자 비밀번호는 8자 이상이어야 합니다.';
    }

    if ($adminPassword !== $adminPasswordConfirm) {
        $errors[] = '관리자 비밀번호 확인이 일치하지 않습니다.';
    }

    if ($errors === []) {
        $checkBaseUrl = toy_current_base_url();
        if ($checkBaseUrl !== '' && !toy_is_local_host($checkBaseUrl)) {
            if (parse_url($checkBaseUrl, PHP_URL_SCHEME) !== 'https') {
                $errors[] = '운영 설치는 HTTPS URL에서 진행하세요.';
            } elseif (!toy_is_public_http_url($checkBaseUrl)) {
                $errors[] = '운영 설치 점검 URL은 공개 라우팅 가능한 host여야 합니다.';
            }

            $publicFindings = $errors === [] ? toy_public_internal_access_findings($checkBaseUrl) : [];
            if ($publicFindings !== []) {
                foreach ($publicFindings as $finding) {
                    $errors[] = '내부 파일이 웹에서 직접 열립니다: ' . (string) $finding['url'];
                }
            }
        }
    }

    if ($errors === []) {
        $installStage = 'prepare_config';
        $existingAppKey = '';
        if (is_file(TOY_ROOT . '/config/config.php')) {
            try {
                $existingConfig = toy_load_config();
                $existingAppKey = is_string($existingConfig['app_key'] ?? null) ? $existingConfig['app_key'] : '';
            } catch (Throwable $ignored) {
                $existingAppKey = '';
            }
        }

        $config = [
            'env' => 'production',
            'debug' => false,
            'timezone' => $values['timezone'],
            'app_key' => $existingAppKey !== '' ? $existingAppKey : bin2hex(random_bytes(32)),
            'db' => [
                'host' => $values['db_host'],
                'name' => $values['db_name'],
                'user' => $values['db_user'],
                'password' => $values['db_password'],
                'charset' => 'utf8mb4',
            ],
        ];

        try {
            $installStage = 'write_config';
            toy_write_config($config);
            toy_apply_runtime_config($config);

            $installStage = 'connect_database';
            $pdo = toy_db($config);

            $installStage = 'execute_schema';
            toy_execute_sql_file($pdo, TOY_ROOT . '/database/core/install.sql');
            toy_execute_sql_file($pdo, TOY_ROOT . '/modules/member/install.sql');
            toy_execute_sql_file($pdo, TOY_ROOT . '/modules/admin/install.sql');
            foreach ($selectedOptionalModuleKeys as $moduleKey) {
                toy_execute_sql_file($pdo, TOY_ROOT . '/modules/' . $moduleKey . '/install.sql');
            }

            $installStage = 'save_site_settings';
            $now = toy_now();
            toy_save_site_settings($pdo, [
                'site.name' => ['value' => $values['site_name'], 'type' => 'string'],
                'site.base_url' => ['value' => $values['base_url'], 'type' => 'string'],
                'site.timezone' => ['value' => $values['timezone'], 'type' => 'string'],
                'site.default_locale' => ['value' => $values['default_locale'], 'type' => 'string'],
                'site.status' => ['value' => 'active', 'type' => 'string'],
            ]);

            $modules = [];
            foreach ($requiredModules as $moduleKey => $module) {
                $modules[] = [
                    'module_key' => $moduleKey,
                    'name' => (string) $module['name'],
                    'version' => (string) $module['version'],
                    'status' => 'enabled',
                ];
            }

            foreach ($selectedOptionalModuleKeys as $moduleKey) {
                $module = $optionalModules[$moduleKey];
                $modules[] = [
                    'module_key' => $moduleKey,
                    'name' => (string) $module['name'],
                    'version' => (string) $module['version'],
                    'status' => 'enabled',
                ];
            }

            $installStage = 'register_modules';
            foreach ($modules as $module) {
                $stmt = $pdo->prepare(
                    'INSERT INTO toy_modules (module_key, name, version, status, is_bundled, installed_at, updated_at)
                     VALUES (:module_key, :name, :version, :status, :is_bundled, :installed_at, :updated_at)
                     ON DUPLICATE KEY UPDATE version = VALUES(version), status = VALUES(status), updated_at = VALUES(updated_at)'
                );
                $stmt->execute([
                    'module_key' => $module['module_key'],
                    'name' => $module['name'],
                    'version' => $module['version'],
                    'status' => $module['status'],
                    'is_bundled' => 1,
                    'installed_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $installStage = 'record_schema_versions';
            toy_record_schema_version($pdo, 'core', '', '2026.04.001');
            toy_record_schema_version($pdo, 'core', '', '2026.04.002');
            toy_record_schema_version($pdo, 'core', '', '2026.04.003');
            toy_record_schema_version($pdo, 'core', '', '2026.04.004');
            toy_record_schema_version($pdo, 'core', '', '2026.04.005');
            toy_record_schema_version($pdo, 'module', 'member', '2026.04.001');
            toy_record_schema_version($pdo, 'module', 'member', '2026.04.002');
            toy_record_schema_version($pdo, 'module', 'member', '2026.04.003');
            toy_record_schema_version($pdo, 'module', 'member', '2026.04.004');
            toy_record_schema_version($pdo, 'module', 'member', '2026.04.005');
            toy_record_schema_version($pdo, 'module', 'member', '2026.04.006');
            toy_record_schema_version($pdo, 'module', 'admin', '2026.04.001');
            foreach ($selectedOptionalModuleKeys as $moduleKey) {
                if ($moduleKey === 'seo') {
                    toy_record_schema_version($pdo, 'module', 'seo', '2026.04.001');
                    toy_record_schema_version($pdo, 'module', 'seo', '2026.04.002');
                    continue;
                }

                toy_record_schema_version($pdo, 'module', $moduleKey, (string) $optionalModules[$moduleKey]['version']);
            }

            require TOY_ROOT . '/modules/member/helpers.php';
            require TOY_ROOT . '/modules/admin/helpers.php';

            $installStage = 'create_owner_account';
            $accountId = toy_member_create_account($pdo, $config, [
                'email' => $values['admin_email'],
                'password' => $adminPassword,
                'display_name' => $values['admin_display_name'],
                'locale' => $values['default_locale'],
                'status' => 'active',
                'email_verified_at' => $now,
                'allow_existing_update' => true,
            ]);

            $installStage = 'grant_owner_role';
            toy_admin_grant_role($pdo, $accountId, 'owner');
            toy_audit_log($pdo, [
                'actor_account_id' => $accountId,
                'actor_type' => 'system',
                'event_type' => 'install.completed',
                'target_type' => 'site',
                'target_id' => 'default',
                'result' => 'success',
                'message' => 'Initial installation completed.',
                'metadata' => [
                    'enabled_modules' => array_values(array_merge(array_keys($requiredModules), $selectedOptionalModuleKeys)),
                ],
            ]);

            $installStage = 'write_install_lock';
            $storageDir = TOY_ROOT . '/storage';
            if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true)) {
                throw new RuntimeException('storage directory cannot be created.');
            }

            if (file_put_contents($storageDir . '/installed.lock', $now . "\n", LOCK_EX) === false) {
                throw new RuntimeException('installed.lock cannot be written.');
            }

            toy_clear_operational_marker('install-failed.json');
            toy_redirect('/login?next=/admin');
        } catch (Throwable $exception) {
            toy_log_exception($exception, 'install_failed_' . $installStage);
            $failureMessage = $exception->getMessage();
            $failureMessage = function_exists('mb_substr') ? mb_substr($failureMessage, 0, 500) : substr($failureMessage, 0, 500);
            toy_write_operational_marker('install-failed.json', [
                'stage' => $installStage,
                'message' => $failureMessage,
                'config_written' => is_file(TOY_ROOT . '/config/config.php'),
                'installed_lock_written' => is_file(TOY_ROOT . '/storage/installed.lock'),
            ]);
            $errors[] = '설치 중 오류가 발생했습니다. DB 정보와 권한을 확인하세요.';
            if (!empty($config['debug'])) {
                $errors[] = $exception->getMessage();
            }
        }
    }
}

include TOY_ROOT . '/core/views/install.php';
