<?php

return [
    'name' => 'Member',
    'version' => '2026.04.005',
    'description' => 'Member account and authentication module.',
    'settings' => [
        'login_identifier' => 'email',
        'login_throttle_window_seconds' => 900,
        'login_throttle_account_limit' => 5,
        'login_throttle_ip_limit' => 20,
        'password_reset_throttle_window_seconds' => 900,
        'password_reset_throttle_account_limit' => 3,
        'password_reset_throttle_ip_limit' => 10,
        'email_verification_throttle_window_seconds' => 900,
        'email_verification_throttle_account_limit' => 3,
        'email_verification_throttle_ip_limit' => 20,
        'register_throttle_window_seconds' => 900,
        'register_throttle_ip_limit' => 10,
    ],
];
