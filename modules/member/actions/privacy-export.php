<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

if (toy_request_method() !== 'POST') {
    toy_render_error(405, '허용되지 않는 요청입니다.');
    exit;
}

toy_require_csrf();

$account = toy_member_require_login($pdo);
$export = toy_member_privacy_export_data($pdo, (int) $account['id']);

toy_audit_log($pdo, [
    'actor_account_id' => (int) $account['id'],
    'actor_type' => 'member',
    'event_type' => 'privacy.export.downloaded',
    'target_type' => 'member_account',
    'target_id' => (string) $account['id'],
    'result' => 'success',
    'message' => 'Member privacy export downloaded.',
]);

toy_send_download_headers('application/json; charset=UTF-8', 'toycore-privacy-export-' . (int) $account['id'] . '.json');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
