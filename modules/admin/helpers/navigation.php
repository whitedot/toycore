<?php

declare(strict_types=1);

function toy_admin_module_menu_items(PDO $pdo): array
{
    $items = [];
    foreach (toy_admin_module_menu_groups($pdo) as $group) {
        foreach ($group['items'] as $item) {
            $items[] = $item;
        }
    }

    usort($items, function (array $left, array $right): int {
        return [$left['order'], $left['label'], $left['path']] <=> [$right['order'], $right['label'], $right['path']];
    });

    return $items;
}

function toy_admin_module_menu_groups(PDO $pdo): array
{
    $groups = [];
    $menuFiles = toy_enabled_module_contract_files($pdo, 'admin-menu.php', ['admin']);
    $pathFiles = toy_enabled_module_contract_files($pdo, 'paths.php', ['admin']);

    foreach ($menuFiles as $moduleKey => $file) {
        $menu = toy_load_module_contract_file($moduleKey, $file);
        if (!is_array($menu)) {
            continue;
        }

        $pathsFile = (string) ($pathFiles[$moduleKey] ?? '');
        $paths = $pathsFile !== '' ? toy_load_module_contract_file($moduleKey, $pathsFile) : [];
        $paths = is_array($paths) ? $paths : [];

        $rawItems = isset($menu['items']) && is_array($menu['items']) ? $menu['items'] : $menu;
        $items = [];
        foreach ($rawItems as $rawItem) {
            if (!is_array($rawItem)) {
                continue;
            }

            $label = trim((string) ($rawItem['label'] ?? ''));
            $path = trim((string) ($rawItem['path'] ?? ''));
            if (
                $label === ''
                || preg_match('/\A\/admin(?:\/[a-z0-9][a-z0-9_-]*)*\z/', $path) !== 1
                || !isset($paths['GET ' . $path])
            ) {
                continue;
            }

            $items[] = [
                'module_key' => $moduleKey,
                'label' => $label,
                'path' => $path,
                'order' => (int) ($rawItem['order'] ?? 1000),
            ];
        }

        if ($items === []) {
            continue;
        }

        usort($items, function (array $left, array $right): int {
            return [$left['order'], $left['label'], $left['path']] <=> [$right['order'], $right['label'], $right['path']];
        });

        $groups[] = [
            'module_key' => $moduleKey,
            'label' => toy_admin_module_menu_group_label($moduleKey, $menu),
            'order' => toy_admin_module_menu_group_order($items, $menu),
            'items' => $items,
        ];
    }

    usort($groups, function (array $left, array $right): int {
        return [$left['order'], $left['label'], $left['module_key']] <=> [$right['order'], $right['label'], $right['module_key']];
    });

    return $groups;
}

function toy_admin_module_menu_group_label(string $moduleKey, array $menu): string
{
    $label = '';
    if (isset($menu['items']) && is_array($menu['items'])) {
        $label = trim((string) ($menu['label'] ?? ''));
    }

    if ($label !== '') {
        return $label;
    }

    $metadata = toy_module_metadata($moduleKey);
    $name = trim((string) ($metadata['name'] ?? ''));

    return $name !== '' ? $name : $moduleKey;
}

function toy_admin_module_menu_group_order(array $items, array $menu): int
{
    if (isset($menu['items']) && is_array($menu['items']) && isset($menu['order'])) {
        return (int) $menu['order'];
    }

    $order = 1000;
    foreach ($items as $item) {
        $order = min($order, (int) ($item['order'] ?? 1000));
    }

    return $order;
}
