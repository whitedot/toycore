<?php

declare(strict_types=1);

function toy_admin_dashboard_modules(PDO $pdo): array
{
    $modules = [];
    $stmt = $pdo->query('SELECT module_key, name, version, status FROM toy_modules ORDER BY id ASC');
    foreach ($stmt->fetchAll() as $row) {
        $modules[] = $row;
    }

    return $modules;
}

function toy_admin_dashboard_table_exists(PDO $pdo, string $tableName): bool
{
    if (preg_match('/\Atoy_[a-z0-9_]{1,80}\z/', $tableName) !== 1) {
        return false;
    }

    try {
        $pdo->query('SELECT 1 FROM ' . $tableName . ' LIMIT 1');
        return true;
    } catch (PDOException $exception) {
        return false;
    }
}

function toy_admin_dashboard_count(PDO $pdo, string $sql): int
{
    try {
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return is_array($row) ? (int) $row['count_value'] : 0;
    } catch (PDOException $exception) {
        return 0;
    }
}

function toy_admin_dashboard_operation_summary(PDO $pdo): array
{
    $operationSummary = [];

    if (toy_admin_dashboard_table_exists($pdo, 'toy_site_menus') && toy_admin_dashboard_table_exists($pdo, 'toy_site_menu_items')) {
        $operationSummary[] = [
            'label' => '사이트 메뉴',
            'value' => (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_site_menus WHERE status = 'enabled'"),
            'detail' => '활성 메뉴 / 항목 ' . (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_site_menu_items WHERE status = 'enabled'"),
        ];
    }

    if (toy_admin_dashboard_table_exists($pdo, 'toy_banners')) {
        $operationSummary[] = [
            'label' => '배너',
            'value' => (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_banners WHERE status = 'enabled'"),
            'detail' => '활성 배너 / 임시저장 ' . (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_banners WHERE status = 'draft'"),
        ];
    }

    if (toy_admin_dashboard_table_exists($pdo, 'toy_notifications') && toy_admin_dashboard_table_exists($pdo, 'toy_notification_deliveries')) {
        $operationSummary[] = [
            'label' => '알림',
            'value' => (string) toy_admin_dashboard_count($pdo, 'SELECT COUNT(*) AS count_value FROM toy_notifications'),
            'detail' => '전체 알림 / 발송 대기 ' . (string) toy_admin_dashboard_count($pdo, "SELECT COUNT(*) AS count_value FROM toy_notification_deliveries WHERE status = 'queued'"),
        ];
    }

    return $operationSummary;
}

function toy_admin_dashboard_auth_runtime_summary(PDO $pdo, array $config): array
{
    $summary = [];
    $session = isset($config['session']) && is_array($config['session']) ? $config['session'] : [];
    $security = isset($config['security']) && is_array($config['security']) ? $config['security'] : [];
    $secrets = isset($config['secrets']) && is_array($config['secrets']) ? $config['secrets'] : [];
    $mail = isset($config['mail']) && is_array($config['mail']) ? $config['mail'] : [];

    $sessionHandler = (string) ($session['handler'] ?? 'database');
    $hasRuntimeSessionsTable = toy_admin_dashboard_table_exists($pdo, 'toy_sessions');
    $summary[] = [
        'label' => 'PHP 세션 저장소',
        'value' => $sessionHandler === 'database' ? 'DB' : '파일',
        'state' => $sessionHandler === 'database' && $hasRuntimeSessionsTable ? '정상' : '주의',
        'detail' => $sessionHandler === 'database'
            ? ($hasRuntimeSessionsTable ? 'toy_sessions 사용 가능' : 'toy_sessions 테이블이 없어 파일 세션으로 fallback')
            : '다중 인스턴스에서는 공유 세션 저장소가 필요',
    ];

    $hasRateLimitsTable = toy_admin_dashboard_table_exists($pdo, 'toy_rate_limits');
    $summary[] = [
        'label' => '인증 제한 저장소',
        'value' => $hasRateLimitsTable ? '전용 테이블' : '인증 로그 fallback',
        'state' => $hasRateLimitsTable ? '정상' : '주의',
        'detail' => $hasRateLimitsTable ? 'toy_rate_limits 사용 가능' : '업데이트 SQL 적용 전에는 인증 로그 COUNT를 사용',
    ];

    $secureCookie = toy_session_cookie_secure($config);
    $summary[] = [
        'label' => '세션 쿠키 Secure',
        'value' => $secureCookie ? '적용' : '미적용',
        'state' => $secureCookie ? '정상' : ((string) ($config['env'] ?? 'production') === 'production' ? '주의' : '확인'),
        'detail' => !empty($security['force_https'])
            ? 'force_https 설정으로 강제'
            : (toy_is_https_request($config) ? '현재 요청을 HTTPS로 인식' : '현재 요청을 HTTPS로 인식하지 않음'),
    ];

    $trustedProxies = isset($security['trusted_proxies']) && is_array($security['trusted_proxies']) ? $security['trusted_proxies'] : [];
    $hasForwardedHeaders = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '') !== '' || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== '';
    $summary[] = [
        'label' => 'Trusted proxy',
        'value' => (string) count($trustedProxies),
        'state' => $trustedProxies !== [] ? '정상' : ($hasForwardedHeaders ? '주의' : '확인'),
        'detail' => $trustedProxies !== []
            ? '프록시 헤더 신뢰 범위가 설정됨'
            : ($hasForwardedHeaders ? '전달 헤더가 있지만 trusted_proxies가 비어 있음' : '프록시 없이 직접 요청으로 판단'),
    ];

    $appKeyEnv = (string) ($secrets['app_key_env'] ?? '');
    $appKeyFromEnv = $appKeyEnv !== '' && getenv($appKeyEnv) !== false && (string) getenv($appKeyEnv) !== '';
    $summary[] = [
        'label' => 'App key 출처',
        'value' => $appKeyFromEnv ? '환경변수' : 'config 파일',
        'state' => toy_app_key($config) !== '' ? '정상' : '주의',
        'detail' => $appKeyFromEnv ? $appKeyEnv . ' 값을 사용 중' : '환경변수 주입이 없으면 config/app_key를 사용',
    ];

    $transport = (string) ($mail['transport'] ?? 'php_mail');
    $mailReady = toy_admin_dashboard_mail_transport_ready($transport, $mail);
    $summary[] = [
        'label' => '메일 transport',
        'value' => $transport,
        'state' => $mailReady ? '정상' : '주의',
        'detail' => $mailReady ? '인증 메일 발송 설정 확인됨' : '인증 메일 발송 설정 보완 필요',
    ];

    return $summary;
}

function toy_admin_dashboard_mail_transport_ready(string $transport, array $mail): bool
{
    if ($transport === 'php_mail') {
        return function_exists('mail');
    }

    if ($transport === 'smtp') {
        return (string) ($mail['host'] ?? '') !== ''
            && (int) ($mail['port'] ?? 0) >= 1
            && filter_var((string) ($mail['from_email'] ?? ''), FILTER_VALIDATE_EMAIL) !== false;
    }

    if ($transport === 'http_api') {
        return toy_is_http_url((string) ($mail['endpoint'] ?? ''));
    }

    return false;
}

function toy_admin_dashboard_recovery_marker(string $filename, string $label): ?array
{
    if (preg_match('/\A[a-z0-9_.-]+\.json\z/', $filename) !== 1) {
        return null;
    }

    $path = TOY_ROOT . '/storage/' . $filename;
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }

    $content = file_get_contents($path);
    $decoded = is_string($content) ? json_decode($content, true) : null;
    if (!is_array($decoded)) {
        return [
            'label' => $label,
            'filename' => $filename,
            'recorded_at' => '',
            'stage' => '',
            'message' => '복구 marker를 읽을 수 없습니다.',
        ];
    }

    return [
        'label' => $label,
        'filename' => $filename,
        'recorded_at' => (string) ($decoded['recorded_at'] ?? ''),
        'stage' => (string) ($decoded['stage'] ?? ''),
        'scope' => (string) ($decoded['scope'] ?? ''),
        'module_key' => (string) ($decoded['module_key'] ?? ''),
        'version' => (string) ($decoded['version'] ?? ''),
        'message' => (string) ($decoded['message'] ?? ''),
    ];
}

function toy_admin_dashboard_recovery_markers(): array
{
    $recoveryMarkers = [];
    foreach ([
        'install-failed.json' => '설치 실패',
        'update-failed.json' => '업데이트 실패',
    ] as $filename => $label) {
        $marker = toy_admin_dashboard_recovery_marker($filename, $label);
        if (is_array($marker)) {
            $recoveryMarkers[] = $marker;
        }
    }

    return $recoveryMarkers;
}

function toy_admin_dashboard_module_backup_summary(): array
{
    $backupRoot = TOY_ROOT . '/storage/module-backups';
    $summary = [
        'count' => 0,
        'latest_name' => '',
        'latest_modified_at' => '',
    ];
    if (!is_dir($backupRoot)) {
        return $summary;
    }

    $directories = glob($backupRoot . '/*', GLOB_ONLYDIR);
    if (!is_array($directories)) {
        return $summary;
    }

    $latestTime = 0;
    foreach ($directories as $directory) {
        $summary['count']++;
        $modifiedAt = filemtime($directory);
        if ($modifiedAt !== false && $modifiedAt > $latestTime) {
            $latestTime = $modifiedAt;
            $summary['latest_name'] = basename($directory);
            $summary['latest_modified_at'] = date('Y-m-d H:i:s', $modifiedAt);
        }
    }

    return $summary;
}
