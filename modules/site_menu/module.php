<?php

return [
    'name' => 'Site Menu',
    'version' => '2026.04.003',
    'type' => 'module',
    'description' => 'Site-wide navigation menu management module.',
    'toycore' => [
        'min_version' => '2026.04.005',
        'tested_with' => ['2026.04.005'],
    ],
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
