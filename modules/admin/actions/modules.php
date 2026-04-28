<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$requiredModules = ['member', 'admin'];
$allowedStatuses = ['enabled', 'disabled'];
$allowedSettingTypes = ['string', 'int', 'bool', 'json'];
$allowedInstallStatuses = ['enabled', 'disabled'];
$errors = [];
$notice = '';

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $intent = toy_post_string('intent', 40);
    $moduleKey = toy_post_string('module_key', 60);

    if (preg_match('/\A[a-z0-9_]+\z/', $moduleKey) !== 1) {
        $errors[] = '모듈 키가 올바르지 않습니다.';
    }

    if ($intent === 'status') {
        $status = toy_post_string('status', 30);
        if (!in_array($status, $allowedStatuses, true)) {
            $errors[] = '모듈 상태 값이 올바르지 않습니다.';
        }

        if (in_array($moduleKey, $requiredModules, true) && $status !== 'enabled') {
            $errors[] = '기본 모듈은 비활성화할 수 없습니다.';
        }
    } elseif ($intent === 'install') {
        $status = toy_post_string('status', 30);
        if (!in_array($status, $allowedInstallStatuses, true)) {
            $errors[] = '설치 후 상태 값이 올바르지 않습니다.';
        }
    }

    if ($errors === [] && $intent === 'install') {
        $moduleDir = TOY_ROOT . '/modules/' . $moduleKey;
        $realModulesDir = realpath(TOY_ROOT . '/modules');
        $realModuleDir = realpath($moduleDir);
        $installSql = $moduleDir . '/install.sql';
        $metadata = toy_module_metadata($moduleKey);

        if ($realModulesDir === false || $realModuleDir === false || strpos($realModuleDir, $realModulesDir . DIRECTORY_SEPARATOR) !== 0) {
            $errors[] = '설치할 모듈 디렉터리를 찾을 수 없습니다.';
        }

        if ($errors === [] && $metadata === []) {
            $errors[] = '모듈 메타데이터를 찾을 수 없습니다.';
        }

        if ($errors === [] && !is_file($installSql)) {
            $errors[] = '모듈 설치 SQL 파일을 찾을 수 없습니다.';
        }

        if ($errors === []) {
            $stmt = $pdo->prepare('SELECT id FROM toy_modules WHERE module_key = :module_key LIMIT 1');
            $stmt->execute(['module_key' => $moduleKey]);
            if (is_array($stmt->fetch())) {
                $errors[] = '이미 설치된 모듈입니다.';
            }
        }
    } elseif ($errors === []) {
        $stmt = $pdo->prepare('SELECT id, status FROM toy_modules WHERE module_key = :module_key LIMIT 1');
        $stmt->execute(['module_key' => $moduleKey]);
        $module = $stmt->fetch();

        if (!is_array($module)) {
            $errors[] = '모듈을 찾을 수 없습니다.';
        }
    }

    if ($errors === [] && $intent === 'install') {
        try {
            toy_execute_sql_file($pdo, $installSql);

            $now = toy_now();
            $moduleName = is_string($metadata['name'] ?? null) && (string) $metadata['name'] !== ''
                ? (string) $metadata['name']
                : $moduleKey;
            $moduleVersion = is_string($metadata['version'] ?? null) && (string) $metadata['version'] !== ''
                ? (string) $metadata['version']
                : '2026.04.001';

            $stmt = $pdo->prepare(
                'INSERT INTO toy_modules (module_key, name, version, status, is_bundled, installed_at, updated_at)
                 VALUES (:module_key, :name, :version, :status, :is_bundled, :installed_at, :updated_at)'
            );
            $stmt->execute([
                'module_key' => $moduleKey,
                'name' => $moduleName,
                'version' => $moduleVersion,
                'status' => $status,
                'is_bundled' => 0,
                'installed_at' => $now,
                'updated_at' => $now,
            ]);

            toy_record_schema_version($pdo, 'module', $moduleKey, $moduleVersion);

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'module.installed',
                'target_type' => 'module',
                'target_id' => $moduleKey,
                'result' => 'success',
                'message' => 'Module installed.',
                'metadata' => [
                    'status' => $status,
                    'version' => $moduleVersion,
                ],
            ]);

            $notice = '모듈을 설치했습니다.';
        } catch (Throwable $exception) {
            toy_log_exception($exception, 'module_install_failed');
            $errors[] = '모듈 설치 중 오류가 발생했습니다.';
        }
    } elseif ($errors === [] && $intent === 'module_setting') {
        $settingKey = toy_post_string('setting_key', 120);
        $settingValue = toy_post_string('setting_value', 5000);
        $valueType = toy_post_string('value_type', 20);

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
                'INSERT INTO toy_module_settings
                    (module_id, setting_key, setting_value, value_type, created_at, updated_at)
                 VALUES
                    (:module_id, :setting_key, :setting_value, :value_type, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    value_type = VALUES(value_type),
                    updated_at = VALUES(updated_at)'
            );
            $stmt->execute([
                'module_id' => (int) $module['id'],
                'setting_key' => $settingKey,
                'setting_value' => $settingValue,
                'value_type' => $valueType,
                'created_at' => toy_now(),
                'updated_at' => toy_now(),
            ]);

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'module.setting.saved',
                'target_type' => 'module_setting',
                'target_id' => $moduleKey . ':' . $settingKey,
                'result' => 'success',
                'message' => 'Module setting saved.',
                'metadata' => [
                    'module_key' => $moduleKey,
                    'setting_key' => $settingKey,
                    'value_type' => $valueType,
                ],
            ]);

            $notice = '모듈 설정 항목을 저장했습니다.';
        }
    } elseif ($errors === [] && $intent === 'delete_module_setting') {
        $settingKey = toy_post_string('setting_key', 120);
        if (preg_match('/\A[a-z][a-z0-9_.-]{1,119}\z/', $settingKey) !== 1) {
            $errors[] = '설정 key 형식이 올바르지 않습니다.';
        }

        if ($errors === []) {
            $stmt = $pdo->prepare('DELETE FROM toy_module_settings WHERE module_id = :module_id AND setting_key = :setting_key');
            $stmt->execute([
                'module_id' => (int) $module['id'],
                'setting_key' => $settingKey,
            ]);

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'module.setting.deleted',
                'target_type' => 'module_setting',
                'target_id' => $moduleKey . ':' . $settingKey,
                'result' => 'success',
                'message' => 'Module setting deleted.',
                'metadata' => [
                    'module_key' => $moduleKey,
                    'setting_key' => $settingKey,
                ],
            ]);

            $notice = '모듈 설정 항목을 삭제했습니다.';
        }
    } elseif ($errors === [] && $intent === 'status') {
        $stmt = $pdo->prepare(
            'UPDATE toy_modules
             SET status = :status, updated_at = :updated_at
             WHERE module_key = :module_key'
        );
        $stmt->execute([
            'status' => $status,
            'updated_at' => toy_now(),
            'module_key' => $moduleKey,
        ]);

        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'admin',
            'event_type' => 'module.status.updated',
            'target_type' => 'module',
            'target_id' => $moduleKey,
            'result' => 'success',
            'message' => 'Module status updated.',
            'metadata' => [
                'before_status' => (string) $module['status'],
                'after_status' => $status,
            ],
        ]);

        $notice = '모듈 상태를 저장했습니다.';
    } elseif ($errors === []) {
        $errors[] = '요청한 작업을 처리할 수 없습니다.';
    }
}

$modules = [];
$stmt = $pdo->query('SELECT id, module_key, name, version, status, is_bundled, installed_at, updated_at FROM toy_modules ORDER BY id ASC');
$installedModuleKeys = [];
foreach ($stmt->fetchAll() as $row) {
    $installedModuleKeys[(string) $row['module_key']] = true;
    $metadata = toy_module_metadata((string) $row['module_key']);
    $row['code_name'] = is_string($metadata['name'] ?? null) ? (string) $metadata['name'] : '';
    $row['code_version'] = is_string($metadata['version'] ?? null) ? (string) $metadata['version'] : '';
    $row['code_type'] = toy_module_type((string) $row['module_key']);
    $row['description'] = is_string($metadata['description'] ?? null) ? (string) $metadata['description'] : '';
    $modules[] = $row;
}

$installableModules = [];
$moduleDirectories = glob(TOY_ROOT . '/modules/*', GLOB_ONLYDIR);
if (is_array($moduleDirectories)) {
    sort($moduleDirectories, SORT_STRING);
    foreach ($moduleDirectories as $moduleDirectory) {
        $moduleKey = basename($moduleDirectory);
        if (!toy_is_safe_module_key($moduleKey) || isset($installedModuleKeys[$moduleKey])) {
            continue;
        }

        $metadata = toy_module_metadata($moduleKey);
        if ($metadata === [] || !is_file($moduleDirectory . '/install.sql')) {
            continue;
        }

        $installableModules[] = [
            'module_key' => $moduleKey,
            'name' => is_string($metadata['name'] ?? null) ? (string) $metadata['name'] : $moduleKey,
            'version' => is_string($metadata['version'] ?? null) ? (string) $metadata['version'] : '',
            'type' => toy_module_type($moduleKey),
            'description' => is_string($metadata['description'] ?? null) ? (string) $metadata['description'] : '',
        ];
    }
}

$moduleSettings = [];
$stmt = $pdo->query(
    'SELECT m.module_key, s.setting_key, s.setting_value, s.value_type, s.updated_at
     FROM toy_module_settings s
     INNER JOIN toy_modules m ON m.id = s.module_id
     ORDER BY m.module_key ASC, s.setting_key ASC'
);
foreach ($stmt->fetchAll() as $row) {
    $moduleSettings[] = $row;
}

include TOY_ROOT . '/modules/admin/views/modules.php';
