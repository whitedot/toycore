#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$distRoot = $root . '/dist';
$expectedVersion = (string) ($argv[1] ?? '');

if ($expectedVersion !== '' && preg_match('/\A(?:dev|\d{4}\.\d{2}\.\d{3})\z/', $expectedVersion) !== 1) {
    fwrite(STDERR, "Usage: php .tools/bin/check-distributions.php [dev|YYYY.MM.NNN]\n");
    exit(1);
}

$packages = [
    'toycore-minimal' => ['member', 'admin'],
    'toycore-standard' => ['member', 'admin', 'seo', 'popup_layer', 'point', 'deposit', 'reward'],
    'toycore-ops' => ['member', 'admin', 'seo', 'popup_layer', 'point', 'deposit', 'reward', 'site_menu', 'banner', 'notification'],
];

$errors = [];

function toy_distribution_error(string $message): void
{
    global $errors;
    $errors[] = $message;
}

function toy_distribution_read_json(string $path): array
{
    $content = file_get_contents($path);
    if (!is_string($content)) {
        toy_distribution_error('Cannot read JSON file: ' . $path);
        return [];
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        toy_distribution_error('Invalid JSON file: ' . $path);
        return [];
    }

    return $decoded;
}

function toy_distribution_module_version(string $moduleDir): string
{
    $moduleFile = $moduleDir . '/module.php';
    if (!is_file($moduleFile)) {
        return '';
    }

    $metadata = include $moduleFile;
    if (!is_array($metadata)) {
        return '';
    }

    return (string) ($metadata['version'] ?? '');
}

function toy_distribution_validate_common_files(string $packageRoot): void
{
    foreach ([
        'README.md',
        'index.php',
        'assets/toycore.css',
        'config/.gitignore',
        'core',
        'database',
        'docs/module-index.json',
        'modules/member/module.php',
        'modules/admin/module.php',
    ] as $path) {
        if (!file_exists($packageRoot . '/' . $path)) {
            toy_distribution_error('Distribution file is missing: ' . $packageRoot . '/' . $path);
        }
    }
}

function toy_distribution_validate_manifest(string $packageName, string $packageRoot, array $expectedModules, string $expectedVersion): void
{
    $manifestPath = $packageRoot . '/distribution-manifest.json';
    if (!is_file($manifestPath)) {
        toy_distribution_error('Distribution manifest is missing: ' . $manifestPath);
        return;
    }

    $manifest = toy_distribution_read_json($manifestPath);
    if ((string) ($manifest['package'] ?? '') !== $packageName) {
        toy_distribution_error('Distribution manifest package mismatch: ' . $manifestPath);
    }

    $manifestVersion = (string) ($manifest['version'] ?? '');
    if ($expectedVersion !== '' && $manifestVersion !== $expectedVersion) {
        toy_distribution_error('Distribution manifest version mismatch: ' . $manifestPath);
    }

    if (!is_array($manifest['modules'] ?? null)) {
        toy_distribution_error('Distribution manifest modules are missing: ' . $manifestPath);
        return;
    }

    $manifestModules = [];
    foreach ($manifest['modules'] as $module) {
        if (!is_array($module)) {
            continue;
        }

        $manifestModules[(string) ($module['module_key'] ?? '')] = (string) ($module['version'] ?? '');
    }

    if (array_keys($manifestModules) !== $expectedModules) {
        toy_distribution_error('Distribution manifest module list mismatch: ' . $manifestPath);
    }

    foreach ($expectedModules as $moduleKey) {
        $moduleDir = $packageRoot . '/modules/' . $moduleKey;
        if (!is_dir($moduleDir)) {
            toy_distribution_error('Distribution module is missing: ' . $moduleDir);
            continue;
        }

        $codeVersion = toy_distribution_module_version($moduleDir);
        if ($codeVersion === '') {
            toy_distribution_error('Distribution module version is missing: ' . $moduleDir);
            continue;
        }

        if (($manifestModules[$moduleKey] ?? '') !== $codeVersion) {
            toy_distribution_error('Distribution manifest module version mismatch: ' . $moduleDir);
        }
    }
}

if (!is_dir($distRoot)) {
    fwrite(STDERR, "dist directory does not exist. Run ./.tools/bin/package-distributions first.\n");
    exit(1);
}

foreach ($packages as $packageName => $expectedModules) {
    $packageRoot = $distRoot . '/' . $packageName;
    if (!is_dir($packageRoot)) {
        toy_distribution_error('Distribution directory is missing: ' . $packageRoot);
        continue;
    }

    toy_distribution_validate_common_files($packageRoot);
    toy_distribution_validate_manifest($packageName, $packageRoot, $expectedModules, $expectedVersion);
}

if ($errors !== []) {
    fwrite(STDERR, "toycore distribution checks failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

echo "toycore distribution checks completed.\n";
