<?php

return [
    'name' => 'Sample Notice',
    'version' => '2026.04.001',
    'type' => 'module',
    'description' => 'Minimal sample module for Toycore extension contracts.',
    'requires' => [
        'modules' => ['admin'],
    ],
    'contracts' => [
        'provides' => [
            'admin-menu.php',
            'output-slots.php',
        ],
    ],
];
