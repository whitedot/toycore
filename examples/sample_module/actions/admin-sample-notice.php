<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$adminPageTitle = '샘플 공지';
include TOY_ROOT . '/modules/admin/views/layout-header.php';
?>

<p>샘플 모듈 관리자 화면입니다.</p>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
