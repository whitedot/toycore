<?php

return [
    'name' => 'Notification',
    'version' => '2026.04.001',
    'type' => 'module',
    'description' => 'Site notification and external delivery queue module.',
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
