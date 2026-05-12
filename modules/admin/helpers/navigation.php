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

function toy_admin_navigation_groups(PDO $pdo): array
{
    $groupsByLabel = [];
    foreach (array_merge(toy_admin_builtin_menu_groups($pdo), toy_admin_module_menu_groups($pdo)) as $group) {
        if (!is_array($group)) {
            continue;
        }

        $label = trim((string) ($group['label'] ?? ''));
        $items = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];
        if ($label === '' || $items === []) {
            continue;
        }

        if (!isset($groupsByLabel[$label])) {
            $groupsByLabel[$label] = [
                'module_key' => (string) ($group['module_key'] ?? ''),
                'label' => $label,
                'order' => (int) ($group['order'] ?? 1000),
                'items' => [],
            ];
        } else {
            $groupsByLabel[$label]['order'] = min((int) $groupsByLabel[$label]['order'], (int) ($group['order'] ?? 1000));
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $path = (string) ($item['path'] ?? '');
            if ($path === '') {
                continue;
            }

            $groupsByLabel[$label]['items'][$path] = $item;
        }
    }

    $groups = array_values($groupsByLabel);
    foreach ($groups as &$group) {
        $group['items'] = array_values($group['items']);
        usort($group['items'], function (array $left, array $right): int {
            return [$left['order'], $left['label'], $left['path']] <=> [$right['order'], $right['label'], $right['path']];
        });
    }
    unset($group);

    usort($groups, function (array $left, array $right): int {
        return [$left['order'], $left['label']] <=> [$right['order'], $right['label']];
    });

    return $groups;
}

function toy_admin_builtin_menu_groups(PDO $pdo): array
{
    $pathsFile = TOY_ROOT . '/modules/admin/paths.php';
    $paths = is_file($pathsFile) ? include $pathsFile : [];
    $paths = is_array($paths) ? $paths : [];

    $groups = [
        [
            'module_key' => 'admin',
            'label' => '관리',
            'order' => 0,
            'items' => [
                ['label' => '대시보드', 'path' => '/admin', 'order' => 10],
                ['label' => '권한', 'path' => '/admin/roles', 'order' => 20],
                ['label' => '관리자 작업 로그', 'path' => '/admin/audit-logs', 'order' => 30],
            ],
        ],
        [
            'module_key' => 'admin',
            'label' => '시스템',
            'order' => 5,
            'items' => [
                ['label' => '설정', 'path' => '/admin/settings', 'order' => 10],
                ['label' => '모듈', 'path' => '/admin/modules', 'order' => 20],
                ['label' => '업데이트', 'path' => '/admin/updates', 'order' => 30],
                ['label' => '보관 정리', 'path' => '/admin/retention', 'order' => 40],
            ],
        ],
        [
            'module_key' => 'admin',
            'label' => '회원',
            'order' => 10,
            'items' => [
                ['label' => '회원 목록', 'path' => '/admin/members', 'order' => 5],
                ['label' => '개인정보 요청', 'path' => '/admin/privacy-requests', 'order' => 30],
            ],
        ],
    ];

    foreach ($groups as &$group) {
        $items = [];
        foreach ($group['items'] as $item) {
            $path = (string) ($item['path'] ?? '');
            if ($path !== '' && isset($paths['GET ' . $path])) {
                $items[] = $item;
            }
        }

        $group['items'] = $items;
    }
    unset($group);

    return $groups;
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
