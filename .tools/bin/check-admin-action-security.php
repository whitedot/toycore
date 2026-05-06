#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
define('TOY_ROOT', $root);
chdir($root);

$errors = [];

function toy_admin_action_security_module_dirs(string $root): array
{
    $dirs = [];
    if (!is_dir($root . '/modules')) {
        return [];
    }

    foreach (new DirectoryIterator($root . '/modules') as $entry) {
        if ($entry->isDot() || !$entry->isDir()) {
            continue;
        }

        $dirs[] = $entry->getPathname();
    }

    sort($dirs, SORT_STRING);
    return $dirs;
}

function toy_admin_action_security_path_is_safe(string $path): bool
{
    if ($path === '' || strpos($path, '..') !== false || strpos($path, '\\') !== false) {
        return false;
    }

    return preg_match('/\Aactions\/[a-z0-9_\-\/]+\.php\z/', $path) === 1;
}

foreach (toy_admin_action_security_module_dirs($root) as $moduleDir) {
    $pathsFile = $moduleDir . '/paths.php';
    if (!is_file($pathsFile)) {
        continue;
    }

    $paths = include $pathsFile;
    if (!is_array($paths)) {
        $errors[] = 'Module paths.php must return an array: ' . $pathsFile;
        continue;
    }

    foreach ($paths as $route => $actionRelativePath) {
        $route = (string) $route;
        $actionRelativePath = (string) $actionRelativePath;
        if (preg_match('/\A(GET|POST) (\/.*)\z/', $route, $matches) !== 1) {
            $errors[] = 'Route key format is invalid: ' . $pathsFile . ' ' . $route;
            continue;
        }

        if (!toy_admin_action_security_path_is_safe($actionRelativePath)) {
            $errors[] = 'Action path is unsafe: ' . $pathsFile . ' ' . $route . ' -> ' . $actionRelativePath;
            continue;
        }

        $method = (string) $matches[1];
        $path = (string) $matches[2];
        $actionFile = $moduleDir . '/' . $actionRelativePath;
        if (!is_file($actionFile)) {
            $errors[] = 'Action file is missing: ' . $pathsFile . ' ' . $route . ' -> ' . $actionRelativePath;
            continue;
        }

        $content = file_get_contents($actionFile);
        if (!is_string($content)) {
            $errors[] = 'Action file cannot be read: ' . $actionFile;
            continue;
        }

        if ($method === 'POST' && strpos($content, 'toy_require_csrf(') === false) {
            $errors[] = 'POST action must require CSRF: ' . $route . ' -> ' . $actionFile;
        }

        if (str_starts_with($path, '/admin')) {
            if (strpos($content, 'toy_member_require_login(') === false) {
                $errors[] = 'Admin action must require login: ' . $route . ' -> ' . $actionFile;
            }

            if (strpos($content, 'toy_admin_require_role(') === false) {
                $errors[] = 'Admin action must require an admin role: ' . $route . ' -> ' . $actionFile;
            }
        }
    }
}

$adminRolesHelper = file_get_contents($root . '/modules/admin/helpers/roles.php');
if (!is_string($adminRolesHelper) || strpos($adminRolesHelper, 'function toy_admin_active_owner_count') === false) {
    $errors[] = 'Admin role helper must expose an active owner count guard.';
} elseif (
    strpos($adminRolesHelper, 'toy_admin_active_owner_count($pdo) <= 1') === false
    || strpos($adminRolesHelper, '마지막 active owner 권한은 회수할 수 없습니다.') === false
) {
    $errors[] = 'Admin role helper must prevent revoking the last active owner role.';
}

$adminMembersHelper = file_get_contents($root . '/modules/admin/helpers/members.php');
if (!is_string($adminMembersHelper)) {
    $errors[] = 'Admin members helper cannot be read.';
} else {
    if (
        strpos($adminMembersHelper, "in_array(\$intent, ['status', 'revoke_sessions'], true)") === false
        || strpos($adminMembersHelper, '회원 작업 값이 올바르지 않습니다.') === false
    ) {
        $errors[] = 'Admin members helper must allowlist member management intents.';
    }

    if (
        strpos($adminMembersHelper, 'toy_admin_current_roles($pdo, $targetAccountId)') === false
        || strpos($adminMembersHelper, 'toy_admin_has_role($pdo, (int) $account[\'id\'], [\'owner\'])') === false
        || strpos($adminMembersHelper, 'owner 계정 상태와 세션은 owner만 변경할 수 있습니다.') === false
    ) {
        $errors[] = 'Admin members helper must prevent non-owner admins from changing owner accounts.';
    }

    if (
        strpos($adminMembersHelper, 'toy_admin_active_owner_count($pdo) <= 1') === false
        || strpos($adminMembersHelper, '마지막 active owner 계정은 비활성화할 수 없습니다.') === false
    ) {
        $errors[] = 'Admin members helper must prevent deactivating the last active owner.';
    }
}

$adminSettingsHelper = file_get_contents($root . '/modules/admin/helpers/settings.php');
if (!is_string($adminSettingsHelper)) {
    $errors[] = 'Admin settings helper cannot be read.';
} elseif (
    strpos($adminSettingsHelper, "in_array(\$intent, ['site', 'site_setting', 'delete_site_setting'], true)") === false
    || strpos($adminSettingsHelper, '사이트 설정 작업 값이 올바르지 않습니다.') === false
) {
    $errors[] = 'Admin settings helper must allowlist site setting intents.';
}

$adminModuleSourcesHelper = file_get_contents($root . '/modules/admin/helpers/module-sources.php');
if (!is_string($adminModuleSourcesHelper)) {
    $errors[] = 'Admin module sources helper cannot be read.';
} elseif (
    strpos($adminModuleSourcesHelper, 'function toy_admin_zip_entry_is_symlink') === false
    || strpos($adminModuleSourcesHelper, 'toy_admin_zip_entry_is_symlink($zip, $i)') === false
    || strpos($adminModuleSourcesHelper, 'zip 안에 심볼릭 링크가 있습니다.') === false
) {
    $errors[] = 'Admin module source zip checks must reject symlink entries before extraction.';
}
if (is_string($adminModuleSourcesHelper) && (
    strpos($adminModuleSourcesHelper, "preg_match('/[\\x00-\\x1F\\x7F]/', \$name)") === false
    || strpos($adminModuleSourcesHelper, "str_contains(\$name, ':')") === false
)) {
    $errors[] = 'Admin module source zip paths must reject control characters and colon separators.';
}
if (is_string($adminModuleSourcesHelper) && (
    strpos($adminModuleSourcesHelper, 'function toy_admin_is_https_public_url') === false
    || strpos($adminModuleSourcesHelper, "strtolower((string) parse_url(\$url, PHP_URL_SCHEME)) === 'https'") === false
    || strpos($adminModuleSourcesHelper, "toy_admin_is_https_public_url((string) (\$entry['zip_url'] ?? ''))") === false
    || strpos($adminModuleSourcesHelper, 'toy_admin_is_https_public_url($repository)') === false
)) {
    $errors[] = 'Admin module source registry URLs must be restricted to HTTPS public URLs at runtime.';
}

if ($errors !== []) {
    fwrite(STDERR, "admin action security checks failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

echo "admin action security checks completed.\n";
