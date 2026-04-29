<?php

return [
    'name' => 'Popup Layer',
    'version' => '2026.04.001',
    'type' => 'module',
    'description' => 'Popup layer management and rendering module.',
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
            'extension-points.php',
        ],
    ],
];
