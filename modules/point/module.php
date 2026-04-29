<?php

return [
    'name' => 'Point',
    'version' => '2026.04.001',
    'type' => 'module',
    'description' => 'Member point balance and transaction ledger module.',
    'toycore' => [
        'min_version' => '2026.04.005',
        'tested_with' => ['2026.04.005'],
    ],
    'requires' => [
        'modules' => ['member', 'admin'],
    ],
];
