<?php

return [
    'name' => 'Member',
    'version' => '2026.04.004',
    'description' => 'Member account and authentication module.',
    'settings' => [
        'login_identifier' => 'email',
        'login_throttle_window_seconds' => 900,
        'login_throttle_account_limit' => 5,
        'login_throttle_ip_limit' => 20,
    ],
];
