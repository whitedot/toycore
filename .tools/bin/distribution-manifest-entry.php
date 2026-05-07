#!/usr/bin/env php
<?php

declare(strict_types=1);

$moduleKey = (string) ($argv[1] ?? '');
$moduleRoot = (string) ($argv[2] ?? '');

if (preg_match('/\A[a-z][a-z0-9_]{1,39}\z/', $moduleKey) !== 1 || $moduleRoot === '') {
    fwrite(STDERR, "Usage: php .tools/bin/distribution-manifest-entry.php <module-key> <module-root>\n");
    exit(1);
}

$moduleFile = rtrim($moduleRoot, "/\\") . '/module.php';
$metadata = [];
if (is_file($moduleFile)) {
    $loaded = include $moduleFile;
    $metadata = is_array($loaded) ? $loaded : [];
}

$toycore = is_array($metadata['toycore'] ?? null) ? $metadata['toycore'] : [];
echo json_encode([
    'module_key' => $moduleKey,
    'version' => is_string($metadata['version'] ?? null) ? (string) $metadata['version'] : '',
    'min_toycore_version' => is_string($toycore['min_version'] ?? null) ? (string) $toycore['min_version'] : '',
    'module_contract' => is_string($toycore['module_contract'] ?? null) ? (string) $toycore['module_contract'] : '',
], JSON_UNESCAPED_SLASHES) . "\n";
