<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

if (toy_request_method() !== 'POST') {
    toy_render_error(405, '허용되지 않는 요청입니다.');
    exit;
}

toy_require_csrf();

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$requestId = (int) toy_post_string('id', 20);
if ($requestId <= 0) {
    toy_render_error(400, '개인정보 요청을 선택하세요.');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, account_id, request_type, status, requester_snapshot, request_message, admin_note, handled_by_account_id, handled_at, created_at, updated_at
     FROM toy_privacy_requests
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $requestId]);
$privacyRequest = $stmt->fetch();

if (!is_array($privacyRequest)) {
    toy_render_error(404, '개인정보 요청을 찾을 수 없습니다.');
    exit;
}

$export = [
    'exported_at' => toy_now(),
    'privacy_request' => [
        'id' => (int) $privacyRequest['id'],
        'account_id' => $privacyRequest['account_id'] !== null ? (int) $privacyRequest['account_id'] : null,
        'request_type' => (string) $privacyRequest['request_type'],
        'status' => (string) $privacyRequest['status'],
        'requester_snapshot' => (string) $privacyRequest['requester_snapshot'],
        'request_message' => $privacyRequest['request_message'],
        'admin_note' => $privacyRequest['admin_note'],
        'handled_by_account_id' => $privacyRequest['handled_by_account_id'] !== null ? (int) $privacyRequest['handled_by_account_id'] : null,
        'handled_at' => $privacyRequest['handled_at'],
        'created_at' => (string) $privacyRequest['created_at'],
        'updated_at' => (string) $privacyRequest['updated_at'],
    ],
];

if (!empty($privacyRequest['account_id'])) {
    $export['member_data'] = toy_member_privacy_export_data($pdo, (int) $privacyRequest['account_id']);
}

toy_audit_log($pdo, [
    'actor_account_id' => (int) $account['id'],
    'actor_type' => 'admin',
    'event_type' => 'privacy.request.exported',
    'target_type' => 'privacy_request',
    'target_id' => (string) $requestId,
    'result' => 'success',
    'message' => 'Privacy request export downloaded.',
]);

toy_send_download_headers('application/json; charset=UTF-8', 'toycore-privacy-request-' . $requestId . '.json');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
