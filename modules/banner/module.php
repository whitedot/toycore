<?php

return [
    'name' => 'Banner',
    'version' => '2026.04.001',
    'type' => 'module',
    'description' => 'Content banner management module for public output slots.',
    'requires' => [
        'modules' => ['member', 'admin'],
    ],
    'contracts' => [
        'provides' => [
            'output-slots.php',
        ],
        'consumes' => [
            'extension-points.php',
        ],
    ],
];
