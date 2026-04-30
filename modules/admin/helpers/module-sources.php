<?php

declare(strict_types=1);

function toy_admin_parse_upload_size(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    if ($unit === 'g') {
        return (int) ($number * 1024 * 1024 * 1024);
    }

    if ($unit === 'm') {
        return (int) ($number * 1024 * 1024);
    }

    if ($unit === 'k') {
        return (int) ($number * 1024);
    }

    return (int) $number;
}

function toy_admin_module_upload_limit_bytes(): int
{
    $limits = [];
    foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
        $bytes = toy_admin_parse_upload_size((string) ini_get($setting));
        if ($bytes > 0) {
            $limits[] = $bytes;
        }
    }

    $defaultLimit = 10 * 1024 * 1024;
    if ($limits === []) {
        return $defaultLimit;
    }

    return min($defaultLimit, ...$limits);
}

function toy_admin_format_bytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / 1024 / 1024, 1) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }

    return (string) $bytes . ' bytes';
}

function toy_admin_module_source_root(): string
{
    return TOY_ROOT . '/modules';
}

function toy_admin_module_work_dir(string $type): string
{
    if (!in_array($type, ['module-upload', 'module-backups'], true)) {
        throw new InvalidArgumentException('Module work directory type is invalid.');
    }

    $directory = TOY_ROOT . '/storage/' . $type;
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('작업 디렉터리를 만들 수 없습니다.');
    }

    return $directory;
}

function toy_admin_random_suffix(): string
{
    try {
        return bin2hex(random_bytes(6));
    } catch (Throwable $exception) {
        return str_replace('.', '', uniqid('', true));
    }
}

function toy_admin_remove_directory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $realDirectory = realpath($directory);
    $realStorage = realpath(TOY_ROOT . '/storage');
    $realModules = realpath(TOY_ROOT . '/modules');
    $insideStorage = $realDirectory !== false && $realStorage !== false && strpos($realDirectory, $realStorage . DIRECTORY_SEPARATOR) === 0;
    $insideModules = $realDirectory !== false && $realModules !== false && strpos($realDirectory, $realModules . DIRECTORY_SEPARATOR) === 0;
    if (!$insideStorage && !$insideModules) {
        throw new RuntimeException('삭제할 디렉터리 경로가 올바르지 않습니다.');
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($realDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir() && !$item->isLink()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($realDirectory);
}

function toy_admin_copy_directory(string $source, string $target): void
{
    if (!is_dir($source)) {
        throw new RuntimeException('복사할 모듈 디렉터리를 찾을 수 없습니다.');
    }

    if (!mkdir($target, 0755, true) && !is_dir($target)) {
        throw new RuntimeException('모듈 대상 디렉터리를 만들 수 없습니다.');
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        if (!is_string($relative) || $relative === '') {
            continue;
        }

        $targetPath = $target . DIRECTORY_SEPARATOR . $relative;
        if ($item->isLink()) {
            throw new RuntimeException('심볼릭 링크가 포함된 모듈은 업로드할 수 없습니다.');
        }

        if ($item->isDir()) {
            if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true)) {
                throw new RuntimeException('모듈 하위 디렉터리를 만들 수 없습니다.');
            }
        } elseif (!copy($item->getPathname(), $targetPath)) {
            throw new RuntimeException('모듈 파일을 복사할 수 없습니다.');
        }
    }
}

function toy_admin_zip_entry_is_safe(string $name): bool
{
    $name = str_replace('\\', '/', $name);
    if ($name === '' || str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('/\A[A-Za-z]:\//', $name) === 1) {
        return false;
    }

    foreach (explode('/', $name) as $segment) {
        if ($segment === '..') {
            return false;
        }
    }

    return true;
}

function toy_admin_infer_module_key_from_filename(string $filename): string
{
    $name = strtolower(pathinfo($filename, PATHINFO_FILENAME));
    $name = preg_replace('/[^a-z0-9_.-]+/', '-', $name);
    $name = is_string($name) ? trim($name, '-_.') : '';
    $name = preg_replace('/-\d{4}\.\d{2}\.\d{3}\z/', '', $name);
    $name = is_string($name) ? $name : '';
    if (str_starts_with($name, 'toycore-module-')) {
        $name = substr($name, strlen('toycore-module-'));
    }

    $moduleKey = str_replace('-', '_', $name);
    return toy_is_safe_module_key($moduleKey) ? $moduleKey : '';
}

function toy_admin_load_module_metadata_from_file(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $metadata = include $file;
    return is_array($metadata) ? $metadata : [];
}

function toy_admin_module_source_candidate(array $candidate): ?array
{
    $moduleKey = (string) ($candidate['module_key'] ?? '');
    $sourceDir = (string) ($candidate['source_dir'] ?? '');
    if (!toy_is_safe_module_key($moduleKey) || !is_dir($sourceDir)) {
        return null;
    }

    $metadata = toy_admin_load_module_metadata_from_file($sourceDir . '/module.php');
    if ($metadata === []) {
        return null;
    }

    return [
        'module_key' => $moduleKey,
        'source_dir' => $sourceDir,
        'metadata' => $metadata,
    ];
}

function toy_admin_find_module_source(string $extractDir, string $requestedModuleKey, string $filename): array
{
    $inferredModuleKey = $requestedModuleKey !== '' ? $requestedModuleKey : toy_admin_infer_module_key_from_filename($filename);
    $candidates = [];

    if ($requestedModuleKey !== '') {
        $candidates[] = [
            'module_key' => $requestedModuleKey,
            'source_dir' => $extractDir . '/' . $requestedModuleKey,
        ];
    }

    $directories = glob($extractDir . '/*', GLOB_ONLYDIR);
    if (is_array($directories)) {
        sort($directories, SORT_STRING);
        foreach ($directories as $directory) {
            $basename = basename($directory);
            if ($basename === 'module') {
                if ($inferredModuleKey !== '') {
                    $candidates[] = [
                        'module_key' => $inferredModuleKey,
                        'source_dir' => $directory,
                    ];
                }
                continue;
            }

            if (toy_is_safe_module_key($basename)) {
                $candidates[] = [
                    'module_key' => $basename,
                    'source_dir' => $directory,
                ];
            }
        }
    }

    if ($inferredModuleKey !== '') {
        $candidates[] = [
            'module_key' => $inferredModuleKey,
            'source_dir' => $extractDir,
        ];
    }

    foreach ($candidates as $candidate) {
        $source = toy_admin_module_source_candidate($candidate);
        if (is_array($source)) {
            return $source;
        }
    }

    throw new RuntimeException('zip 안에서 모듈 구조를 찾을 수 없습니다. 최상위 {module_key}/module.php 구조를 사용하거나 module_key를 입력하세요.');
}

function toy_admin_validate_module_source(string $moduleKey, string $sourceDir, array $metadata): array
{
    $errors = [];
    if (in_array($moduleKey, ['member', 'admin'], true)) {
        $errors[] = 'member와 admin 기본 모듈은 zip 업로드로 교체할 수 없습니다.';
    }

    if (!is_file($sourceDir . '/module.php')) {
        $errors[] = 'module.php 파일이 필요합니다.';
    }

    if (!is_file($sourceDir . '/install.sql')) {
        $errors[] = 'install.sql 파일이 필요합니다.';
    }

    $version = is_string($metadata['version'] ?? null) ? (string) $metadata['version'] : '';
    if ($version === '' || preg_match('/\A\d{4}\.\d{2}\.\d{3}\z/', $version) !== 1) {
        $errors[] = 'module.php의 version은 YYYY.MM.NNN 형식이어야 합니다.';
    }

    $type = (string) ($metadata['type'] ?? 'module');
    if (!in_array($type, ['module', 'plugin'], true)) {
        $errors[] = 'module.php의 type은 module 또는 plugin이어야 합니다.';
    }

    return $errors;
}

function toy_admin_extract_module_upload(array $file, string $requestedModuleKey): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZipArchive 확장이 필요합니다.');
    }

    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('zip 파일 업로드에 실패했습니다.');
    }

    $filename = (string) ($file['name'] ?? '');
    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'zip') {
        throw new RuntimeException('zip 파일만 업로드할 수 있습니다.');
    }

    $size = (int) ($file['size'] ?? 0);
    $limit = toy_admin_module_upload_limit_bytes();
    if ($size <= 0 || $size > $limit) {
        throw new RuntimeException('업로드 파일 크기는 ' . toy_admin_format_bytes($limit) . ' 이하여야 합니다.');
    }

    if ($requestedModuleKey !== '' && !toy_is_safe_module_key($requestedModuleKey)) {
        throw new RuntimeException('입력한 모듈 키가 올바르지 않습니다.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_file($tmpName)) {
        throw new RuntimeException('업로드된 임시 파일을 찾을 수 없습니다.');
    }

    $workRoot = toy_admin_module_work_dir('module-upload');
    $extractDir = $workRoot . '/upload-' . date('YmdHis') . '-' . toy_admin_random_suffix();
    if (!mkdir($extractDir, 0755, true)) {
        throw new RuntimeException('업로드 작업 디렉터리를 만들 수 없습니다.');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpName) !== true) {
        toy_admin_remove_directory($extractDir);
        throw new RuntimeException('zip 파일을 열 수 없습니다.');
    }

    try {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (!is_string($entry) || !toy_admin_zip_entry_is_safe($entry)) {
                throw new RuntimeException('zip 안에 안전하지 않은 경로가 있습니다.');
            }
        }

        if (!$zip->extractTo($extractDir)) {
            throw new RuntimeException('zip 파일을 압축 해제할 수 없습니다.');
        }
    } finally {
        $zip->close();
    }

    try {
        $source = toy_admin_find_module_source($extractDir, $requestedModuleKey, $filename);
        $errors = toy_admin_validate_module_source(
            (string) $source['module_key'],
            (string) $source['source_dir'],
            is_array($source['metadata']) ? $source['metadata'] : []
        );
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $source['extract_dir'] = $extractDir;
        return $source;
    } catch (Throwable $exception) {
        toy_admin_remove_directory($extractDir);
        throw $exception;
    }
}

function toy_admin_install_module_source_files(string $moduleKey, string $sourceDir): array
{
    if (!toy_is_safe_module_key($moduleKey)) {
        throw new InvalidArgumentException('Module key is invalid.');
    }

    $modulesRoot = toy_admin_module_source_root();
    if (!is_dir($modulesRoot) && !mkdir($modulesRoot, 0755, true)) {
        throw new RuntimeException('modules 디렉터리를 만들 수 없습니다.');
    }

    $targetDir = $modulesRoot . '/' . $moduleKey;
    $backupDir = '';
    if (is_dir($targetDir)) {
        $backupRoot = toy_admin_module_work_dir('module-backups');
        $backupDir = $backupRoot . '/' . $moduleKey . '-' . date('YmdHis') . '-' . toy_admin_random_suffix();
        if (!rename($targetDir, $backupDir)) {
            throw new RuntimeException('기존 모듈 디렉터리를 백업할 수 없습니다.');
        }
    }

    try {
        toy_admin_copy_directory($sourceDir, $targetDir);
    } catch (Throwable $exception) {
        if (is_dir($targetDir)) {
            toy_admin_remove_directory($targetDir);
        }

        if ($backupDir !== '' && is_dir($backupDir) && !is_dir($targetDir)) {
            rename($backupDir, $targetDir);
        }

        throw $exception;
    }

    return [
        'target_dir' => $targetDir,
        'backup_dir' => $backupDir,
    ];
}

function toy_admin_module_pending_update_counts(array $pendingUpdates): array
{
    $counts = [];
    foreach ($pendingUpdates as $update) {
        if ((string) ($update['scope'] ?? '') !== 'module') {
            continue;
        }

        $moduleKey = (string) ($update['module_key'] ?? '');
        if (!toy_is_safe_module_key($moduleKey)) {
            continue;
        }

        $counts[$moduleKey] = (int) ($counts[$moduleKey] ?? 0) + 1;
    }

    return $counts;
}

function toy_admin_sync_module_version(PDO $pdo, string $moduleKey, string $newVersion): void
{
    if (!toy_is_safe_module_key($moduleKey) || preg_match('/\A\d{4}\.\d{2}\.\d{3}\z/', $newVersion) !== 1) {
        throw new InvalidArgumentException('Module version is invalid.');
    }

    $stmt = $pdo->prepare(
        'UPDATE toy_modules
         SET version = :version, updated_at = :updated_at
         WHERE module_key = :module_key'
    );
    $stmt->execute([
        'version' => $newVersion,
        'updated_at' => toy_now(),
        'module_key' => $moduleKey,
    ]);
}

function toy_admin_sync_file_only_module_versions(PDO $pdo, array $pendingUpdateCounts): array
{
    $synced = [];
    $stmt = $pdo->query('SELECT module_key, version FROM toy_modules ORDER BY module_key ASC');
    foreach ($stmt->fetchAll() as $module) {
        $moduleKey = (string) ($module['module_key'] ?? '');
        $installedVersion = (string) ($module['version'] ?? '');
        if (!toy_is_safe_module_key($moduleKey) || (int) ($pendingUpdateCounts[$moduleKey] ?? 0) > 0) {
            continue;
        }

        $metadata = toy_module_metadata($moduleKey);
        $codeVersion = is_string($metadata['version'] ?? null) ? (string) $metadata['version'] : '';
        if (preg_match('/\A\d{4}\.\d{2}\.\d{3}\z/', $codeVersion) !== 1 || strcmp($codeVersion, $installedVersion) <= 0) {
            continue;
        }

        toy_admin_sync_module_version($pdo, $moduleKey, $codeVersion);
        $synced[] = [
            'module_key' => $moduleKey,
            'before_version' => $installedVersion,
            'after_version' => $codeVersion,
        ];
    }

    return $synced;
}
