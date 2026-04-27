<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$errors = [];
$notice = '';
$values = [
    'name' => (string) ($site['name'] ?? ''),
    'base_url' => (string) ($site['base_url'] ?? ''),
    'timezone' => (string) ($site['timezone'] ?? 'Asia/Seoul'),
    'default_locale' => (string) ($site['default_locale'] ?? 'ko'),
    'status' => (string) ($site['status'] ?? 'active'),
];

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $values = [
        'name' => toy_post_string('name', 120),
        'base_url' => toy_post_string('base_url', 255),
        'timezone' => toy_post_string('timezone', 80),
        'default_locale' => toy_post_string('default_locale', 20),
        'status' => toy_post_string('status', 30),
    ];

    if ($values['name'] === '') {
        $errors[] = '사이트 이름을 입력하세요.';
    }

    if ($values['base_url'] !== '' && filter_var($values['base_url'], FILTER_VALIDATE_URL) === false) {
        $errors[] = 'Base URL 형식이 올바르지 않습니다.';
    }

    if (!in_array($values['timezone'], timezone_identifiers_list(), true)) {
        $errors[] = 'timezone 값이 올바르지 않습니다.';
    }

    if (!preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/', $values['default_locale'])) {
        $errors[] = '기본 locale 값이 올바르지 않습니다.';
    }

    if (!in_array($values['status'], ['active', 'maintenance'], true)) {
        $errors[] = '운영 상태 값이 올바르지 않습니다.';
    }

    if ($errors === []) {
        $previousValues = [
            'name' => (string) ($site['name'] ?? ''),
            'base_url' => (string) ($site['base_url'] ?? ''),
            'timezone' => (string) ($site['timezone'] ?? ''),
            'default_locale' => (string) ($site['default_locale'] ?? ''),
            'status' => (string) ($site['status'] ?? ''),
        ];

        $stmt = $pdo->prepare(
            'UPDATE toy_sites
             SET name = :name,
                 base_url = :base_url,
                 timezone = :timezone,
                 default_locale = :default_locale,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $values['name'],
            'base_url' => $values['base_url'],
            'timezone' => $values['timezone'],
            'default_locale' => $values['default_locale'],
            'status' => $values['status'],
            'updated_at' => toy_now(),
            'id' => (int) ($site['id'] ?? 0),
        ]);

        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'admin',
            'event_type' => 'site.settings.updated',
            'target_type' => 'site',
            'target_id' => (string) ($site['id'] ?? ''),
            'result' => 'success',
            'message' => 'Site settings updated.',
            'metadata' => [
                'before' => $previousValues,
                'after' => $values,
            ],
        ]);

        $site = toy_load_site($pdo);
        if (is_array($site)) {
            $values = [
                'name' => (string) ($site['name'] ?? ''),
                'base_url' => (string) ($site['base_url'] ?? ''),
                'timezone' => (string) ($site['timezone'] ?? 'Asia/Seoul'),
                'default_locale' => (string) ($site['default_locale'] ?? 'ko'),
                'status' => (string) ($site['status'] ?? 'active'),
            ];
        }

        $notice = '사이트 설정을 저장했습니다.';
    }
}

include TOY_ROOT . '/modules/admin/views/settings.php';
