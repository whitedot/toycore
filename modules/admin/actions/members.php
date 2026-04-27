<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin', 'manager']);

$allowedStatuses = ['active', 'pending', 'suspended', 'withdrawn', 'anonymized'];
$errors = [];
$notice = '';

if (toy_request_method() === 'POST') {
    toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);
    toy_require_csrf();

    $targetAccountId = (int) toy_post_string('account_id', 20);
    $status = toy_post_string('status', 30);

    if ($targetAccountId <= 0) {
        $errors[] = '회원을 선택하세요.';
    }

    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = '회원 상태 값이 올바르지 않습니다.';
    }

    if ($targetAccountId === (int) $account['id'] && $status !== 'active') {
        $errors[] = '현재 로그인한 관리자 계정은 비활성화할 수 없습니다.';
    }

    if ($errors === []) {
        $stmt = $pdo->prepare('SELECT status FROM toy_member_accounts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $targetAccountId]);
        $targetAccount = $stmt->fetch();

        if (!is_array($targetAccount)) {
            $errors[] = '회원을 찾을 수 없습니다.';
        }
    }

    if ($errors === []) {
        $stmt = $pdo->prepare(
            'UPDATE toy_member_accounts
             SET status = :status, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'updated_at' => toy_now(),
            'id' => $targetAccountId,
        ]);

        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'admin',
            'event_type' => 'member.status.updated',
            'target_type' => 'member_account',
            'target_id' => (string) $targetAccountId,
            'result' => 'success',
            'message' => 'Member status updated.',
            'metadata' => [
                'before_status' => (string) $targetAccount['status'],
                'after_status' => $status,
            ],
        ]);

        $notice = '회원 상태를 저장했습니다.';
    }
}

$statusFilter = toy_get_string('status', 30);
if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$members = [];
if ($statusFilter !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, email, display_name, locale, status, email_verified_at, last_login_at, created_at, updated_at
         FROM toy_member_accounts
         WHERE status = :status
         ORDER BY id DESC
         LIMIT 50'
    );
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt = $pdo->query(
        'SELECT id, email, display_name, locale, status, email_verified_at, last_login_at, created_at, updated_at
         FROM toy_member_accounts
         ORDER BY id DESC
         LIMIT 50'
    );
}

foreach ($stmt->fetchAll() as $row) {
    $members[] = $row;
}

include TOY_ROOT . '/modules/admin/views/members.php';
