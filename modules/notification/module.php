<?php

return [
    'name' => 'Notification',
    'version' => '2026.04.001',
    'type' => 'module',
    'description' => 'Site notification and external delivery queue module.',
    'toycore' => [
        'min_version' => '2026.04.005',
        'tested_with' => ['2026.04.005'],
    ],
    'requires' => [
        'modules' => ['member', 'admin'],
    ],
    'contracts' => [
        'provides' => [
            'admin-menu.php',
            'menu-links.php',
            'privacy-export.php',
        ],
    ],
];
