<?php

declare(strict_types=1);

function toy_member_account_select_columns(): string
{
    return 'id, account_identifier_hash, login_id_hash, email, email_hash, password_hash, display_name, locale, status, email_verified_at, last_login_at, created_at, updated_at';
}

function toy_member_default_settings(): array
{
    $metadata = toy_module_metadata('member');
    $settings = isset($metadata['settings']) && is_array($metadata['settings']) ? $metadata['settings'] : [];

    return [
        'allow_registration' => (bool) ($settings['allow_registration'] ?? true),
        'email_verification_enabled' => (bool) ($settings['email_verification_enabled'] ?? true),
        'login_identifier' => is_string($settings['login_identifier'] ?? null) ? (string) $settings['login_identifier'] : 'email',
        'login_throttle_window_seconds' => (int) ($settings['login_throttle_window_seconds'] ?? 900),
        'login_throttle_account_limit' => (int) ($settings['login_throttle_account_limit'] ?? 5),
        'login_throttle_ip_limit' => (int) ($settings['login_throttle_ip_limit'] ?? 20),
        'password_reset_throttle_window_seconds' => (int) ($settings['password_reset_throttle_window_seconds'] ?? 900),
        'password_reset_throttle_account_limit' => (int) ($settings['password_reset_throttle_account_limit'] ?? 3),
        'password_reset_throttle_ip_limit' => (int) ($settings['password_reset_throttle_ip_limit'] ?? 10),
        'email_verification_throttle_window_seconds' => (int) ($settings['email_verification_throttle_window_seconds'] ?? 900),
        'email_verification_throttle_account_limit' => (int) ($settings['email_verification_throttle_account_limit'] ?? 3),
        'email_verification_throttle_ip_limit' => (int) ($settings['email_verification_throttle_ip_limit'] ?? 20),
        'register_throttle_window_seconds' => (int) ($settings['register_throttle_window_seconds'] ?? 900),
        'register_throttle_ip_limit' => (int) ($settings['register_throttle_ip_limit'] ?? 10),
    ];
}

function toy_member_settings(PDO $pdo): array
{
    $settings = array_merge(toy_member_default_settings(), toy_module_settings($pdo, 'member'));

    $settings['allow_registration'] = (bool) $settings['allow_registration'];
    $settings['email_verification_enabled'] = (bool) $settings['email_verification_enabled'];
    $settings['login_identifier'] = (string) $settings['login_identifier'] === 'login_id' ? 'login_id' : 'email';

    foreach (toy_member_integer_setting_keys() as $key => $limits) {
        $settings[$key] = toy_member_clamp_int((int) ($settings[$key] ?? $limits['default']), $limits['min'], $limits['max']);
    }

    return $settings;
}

function toy_member_integer_setting_keys(): array
{
    return [
        'login_throttle_window_seconds' => ['default' => 900, 'min' => 0, 'max' => 86400],
        'login_throttle_account_limit' => ['default' => 5, 'min' => 0, 'max' => 1000],
        'login_throttle_ip_limit' => ['default' => 20, 'min' => 0, 'max' => 1000],
        'password_reset_throttle_window_seconds' => ['default' => 900, 'min' => 0, 'max' => 86400],
        'password_reset_throttle_account_limit' => ['default' => 3, 'min' => 0, 'max' => 1000],
        'password_reset_throttle_ip_limit' => ['default' => 10, 'min' => 0, 'max' => 1000],
        'email_verification_throttle_window_seconds' => ['default' => 900, 'min' => 0, 'max' => 86400],
        'email_verification_throttle_account_limit' => ['default' => 3, 'min' => 0, 'max' => 1000],
        'email_verification_throttle_ip_limit' => ['default' => 20, 'min' => 0, 'max' => 1000],
        'register_throttle_window_seconds' => ['default' => 900, 'min' => 0, 'max' => 86400],
        'register_throttle_ip_limit' => ['default' => 10, 'min' => 0, 'max' => 1000],
    ];
}

function toy_member_clamp_int(int $value, int $min, int $max): int
{
    return max($min, min($max, $value));
}
