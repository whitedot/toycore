<?php

return [
    'name' => 'Site Menu',
    'version' => '2026.04.002',
    'type' => 'module',
    'description' => 'Site-wide navigation menu management module.',
    'requires' => [
        'modules' => ['member', 'admin'],
    ],
    'contracts' => [
        'provides' => [
            'output-slots.php',
        ],
        'consumes' => [
            'menu-links.php',
        ],
    ],
];
