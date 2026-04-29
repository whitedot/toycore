<?php

return [
    'name' => 'Deposit',
    'version' => '2026.04.001',
    'type' => 'module',
    'description' => 'Member deposit balance and transaction ledger module.',
    'toycore' => [
        'min_version' => '2026.04.005',
        'tested_with' => ['2026.04.005'],
    ],
    'requires' => [
        'modules' => ['member', 'admin'],
    ],
];
