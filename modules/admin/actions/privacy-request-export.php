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

$privacyRequest = toy_admin_privacy_request($pdo, $requestId);
if ($privacyRequest === null) {
    toy_render_error(404, '개인정보 요청을 찾을 수 없습니다.');
    exit;
}

$export = toy_admin_privacy_request_export_data($pdo, $privacyRequest);
toy_admin_log_privacy_request_export($pdo, $account, $requestId);

toy_send_download_headers('application/json; charset=UTF-8', 'toycore-privacy-request-' . $requestId . '.json');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
