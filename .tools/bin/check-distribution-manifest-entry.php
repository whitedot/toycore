#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
chdir($root);

$workRoot = $root . '/storage/check-distribution-manifest-entry-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
$moduleRoot = $workRoot . '/module';

function toy_check_distribution_manifest_entry_remove_directory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $realDirectory = realpath($directory);
    $realStorage = realpath(__DIR__ . '/../../storage');
    if ($realDirectory === false || $realStorage === false || strpos($realDirectory, $realStorage . DIRECTORY_SEPARATOR) !== 0) {
        throw new RuntimeException('Refusing to remove unexpected directory: ' . $directory);
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

try {
    if (!mkdir($moduleRoot, 0755, true)) {
        throw new RuntimeException('work directory cannot be created.');
    }

    $modulePhp = "<?php\n\nreturn [\n"
        . "    'name' => 'SEO',\n"
        . "    'version' => '2099.01.001',\n"
        . "    'type' => 'module',\n"
        . "    'toycore' => [\n"
        . "        'min_version' => '0.1.1',\n"
        . "        'tested_with' => ['0.1.1'],\n"
        . "        'module_contract' => '1.0',\n"
        . "    ],\n"
        . "];\n";
    if (file_put_contents($moduleRoot . '/module.php', $modulePhp, LOCK_EX) === false) {
        throw new RuntimeException('module.php cannot be written.');
    }

    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg('.tools/bin/distribution-manifest-entry.php') . ' '
        . escapeshellarg('seo') . ' ' . escapeshellarg($moduleRoot);
    $output = [];
    exec($command . ' 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('manifest entry command failed: ' . implode("\n", $output));
    }

    $entry = json_decode(implode("\n", $output), true);
    if (
        !is_array($entry)
        || (string) ($entry['module_key'] ?? '') !== 'seo'
        || (string) ($entry['version'] ?? '') !== '2099.01.001'
        || (string) ($entry['min_toycore_version'] ?? '') !== '0.1.1'
        || (string) ($entry['module_contract'] ?? '') !== '1.0'
    ) {
        throw new RuntimeException('manifest entry output is invalid.');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "distribution manifest entry checks failed: " . $exception->getMessage() . "\n");
    toy_check_distribution_manifest_entry_remove_directory($workRoot);
    exit(1);
}

toy_check_distribution_manifest_entry_remove_directory($workRoot);
echo "distribution manifest entry checks completed.\n";
