<?php

declare(strict_types=1);

function toy_admin_privacy_request_statuses(): array
{
    return ['requested', 'reviewing', 'completed', 'rejected', 'cancelled'];
}

function toy_admin_handle_privacy_request_post(PDO $pdo, array $account, array $allowedStatuses): array
{
    $errors = [];
    $notice = '';
    $requestId = (int) toy_post_string('request_id', 20);
    $status = toy_post_string('status', 30);
    $adminNote = toy_post_string('admin_note', 2000);
    $identityConfirmed = ($_POST['identity_confirmed'] ?? '') === '1';
    $exportConfirmed = ($_POST['export_confirmed'] ?? '') === '1';
    $actionConfirmed = ($_POST['action_confirmed'] ?? '') === '1';

    if ($requestId <= 0) {
        $errors[] = '요청을 선택하세요.';
    }

    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = '처리 상태 값이 올바르지 않습니다.';
    }

    if ($status === 'completed' && (!$identityConfirmed || !$exportConfirmed || !$actionConfirmed)) {
        $errors[] = '완료 처리 전 요청자 확인, 내보내기/처리 확인, 처리 내용 확인이 필요합니다.';
    }

    if (in_array($status, ['completed', 'rejected', 'cancelled'], true) && $adminNote === '') {
        $errors[] = '종결 상태로 변경할 때는 관리자 메모를 남기세요.';
    }

    if ($errors === []) {
        $stmt = $pdo->prepare('SELECT id, status FROM toy_privacy_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $requestId]);
        $privacyRequest = $stmt->fetch();

        if (!is_array($privacyRequest)) {
            $errors[] = '개인정보 요청을 찾을 수 없습니다.';
        }
    }

    if ($errors === []) {
        $handledAt = in_array($status, ['completed', 'rejected', 'cancelled'], true) ? toy_now() : null;
        $stmt = $pdo->prepare(
            'UPDATE toy_privacy_requests
             SET status = :status,
                 admin_note = :admin_note,
                 handled_by_account_id = :handled_by_account_id,
                 handled_at = :handled_at,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'admin_note' => $adminNote,
            'handled_by_account_id' => (int) $account['id'],
            'handled_at' => $handledAt,
            'updated_at' => toy_now(),
            'id' => $requestId,
        ]);

        toy_audit_log($pdo, [
            'actor_account_id' => (int) $account['id'],
            'actor_type' => 'admin',
            'event_type' => 'privacy.request.updated',
            'target_type' => 'privacy_request',
            'target_id' => (string) $requestId,
            'result' => 'success',
            'message' => 'Privacy request updated.',
            'metadata' => [
                'before_status' => (string) $privacyRequest['status'],
                'after_status' => $status,
                'checklist' => [
                    'identity_confirmed' => $identityConfirmed,
                    'export_confirmed' => $exportConfirmed,
                    'action_confirmed' => $actionConfirmed,
                ],
            ],
        ]);

        $notice = '개인정보 요청 상태를 저장했습니다.';
    }

    return toy_admin_action_result($errors, $notice);
}

function toy_admin_privacy_request_status_filter(array $allowedStatuses): string
{
    $statusFilter = toy_get_string('status', 30);
    if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
        return '';
    }

    return $statusFilter;
}

function toy_admin_privacy_requests(PDO $pdo, string $statusFilter): array
{
    $requests = [];
    if ($statusFilter !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, account_id, request_type, status, requester_snapshot, request_message, admin_note, handled_by_account_id, handled_at, created_at, updated_at
             FROM toy_privacy_requests
             WHERE status = :status
             ORDER BY id DESC
             LIMIT 100'
        );
        $stmt->execute(['status' => $statusFilter]);
    } else {
        $stmt = $pdo->query(
            'SELECT id, account_id, request_type, status, requester_snapshot, request_message, admin_note, handled_by_account_id, handled_at, created_at, updated_at
             FROM toy_privacy_requests
             ORDER BY id DESC
             LIMIT 100'
        );
    }

    foreach ($stmt->fetchAll() as $row) {
        $requests[] = $row;
    }

    return $requests;
}
