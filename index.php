<?php

declare(strict_types=1);

define('TOY_ROOT', __DIR__);

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (is_string($requestPath) && str_starts_with($requestPath, '/assets/')) {
        $staticPath = realpath(TOY_ROOT . $requestPath);
        if (is_string($staticPath) && str_starts_with($staticPath, TOY_ROOT . DIRECTORY_SEPARATOR) && is_file($staticPath)) {
            return false;
        }
    }
}

require TOY_ROOT . '/core/helpers.php';
toy_send_security_headers();

set_exception_handler(function (Throwable $exception): void {
    toy_render_error(500, '서버 오류가 발생했습니다.', $exception);
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

toy_start_session();

$method = toy_request_method();
$path = toy_request_path();

if (!toy_is_installed()) {
    include TOY_ROOT . '/core/actions/install.php';
    exit;
}

$config = toy_load_config();
toy_apply_runtime_config($config);
toy_send_security_headers($config);

try {
    $pdo = toy_db($config);
    $site = toy_load_site($pdo);
    toy_apply_site_runtime_settings($site);
    toy_set_locale(toy_resolve_locale($pdo, $site));
} catch (Throwable $exception) {
    toy_render_error(500, 'DB 연결 또는 사이트 설정을 확인할 수 없습니다.', $exception);
    exit;
}

if (
    $site !== null
    && $site['status'] === 'maintenance'
    && $path !== '/login'
    && $path !== '/logout'
    && strpos($path, '/admin') !== 0
) {
    toy_render_error(503, '현재 점검 중입니다.');
    exit;
}

if ($path === '/') {
    include TOY_ROOT . '/core/views/home.php';
    exit;
}

$moduleKeys = toy_enabled_module_keys($pdo);

foreach ($moduleKeys as $moduleKey) {
    $moduleDir = TOY_ROOT . '/modules/' . $moduleKey;
    $pathsFile = $moduleDir . '/paths.php';

    if (!is_file($pathsFile)) {
        continue;
    }

    $paths = include $pathsFile;
    if (!is_array($paths)) {
        continue;
    }

    $routeKey = $method . ' ' . $path;
    if (!isset($paths[$routeKey])) {
        continue;
    }

    $actionRelativePath = (string) $paths[$routeKey];
    if (!toy_is_safe_module_action($actionRelativePath)) {
        toy_render_error(500, '모듈 action 경로가 올바르지 않습니다.');
        exit;
    }

    $actionFile = $moduleDir . '/' . $actionRelativePath;
    $realModuleDir = realpath($moduleDir);
    $realActionFile = realpath($actionFile);

    if ($realModuleDir === false || $realActionFile === false || strpos($realActionFile, $realModuleDir . DIRECTORY_SEPARATOR) !== 0) {
        toy_render_error(404, '요청한 화면을 찾을 수 없습니다.');
        exit;
    }

    include $realActionFile;
    exit;
}

toy_render_error(404, '요청한 화면을 찾을 수 없습니다.');
