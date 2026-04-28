<?php

$adminPageTitle = $adminPageTitle ?? '관리자';
$seo = [
    'title' => $adminPageTitle,
    'robots' => 'noindex, nofollow',
];
$adminEnabledModules = isset($pdo) && $pdo instanceof PDO ? toy_enabled_module_keys($pdo) : [];
$adminSeoEnabled = in_array('seo', $adminEnabledModules, true);
$adminPopupLayerEnabled = in_array('popup_layer', $adminEnabledModules, true);
?>
<!doctype html>
<html lang="<?php echo toy_e(toy_locale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php echo toy_seo_tags($seo, $site ?? null); ?>
    <?php echo toy_stylesheet_tag(); ?>
</head>
<body>
    <header>
        <h1><?php echo toy_e($adminPageTitle); ?></h1>
        <nav>
            <a href="/admin">대시보드</a>
            <a href="/admin/settings">설정</a>
            <a href="/admin/modules">모듈</a>
            <?php if ($adminSeoEnabled) { ?>
                <a href="/admin/seo">SEO</a>
            <?php } ?>
            <?php if ($adminPopupLayerEnabled) { ?>
                <a href="/admin/popup-layers">팝업레이어</a>
            <?php } ?>
            <a href="/admin/updates">업데이트</a>
            <a href="/admin/members">회원</a>
            <a href="/admin/roles">권한</a>
            <a href="/admin/audit-logs">감사 로그</a>
            <a href="/admin/privacy-requests">개인정보 요청</a>
            <a href="/admin/retention">보관 정리</a>
            <form method="post" action="/logout" style="display:inline">
                <?php echo toy_csrf_field(); ?>
                <button type="submit">로그아웃</button>
            </form>
        </nav>
    </header>
    <main>
