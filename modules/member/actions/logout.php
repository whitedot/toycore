<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

if (toy_request_method() !== 'POST') {
    toy_render_error(405, '허용되지 않는 요청입니다.');
    exit;
}

toy_require_csrf();

$account = toy_member_current_account($pdo);
if ($account !== null) {
    toy_member_log_auth($pdo, (int) $account['id'], 'logout', 'success');
}

toy_member_logout();
toy_redirect('/login');
