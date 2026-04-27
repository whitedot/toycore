<?php

$adminPageTitle = $adminPageTitle ?? '관리자';
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?php echo toy_e($adminPageTitle); ?></title>
</head>
<body>
    <header>
        <h1><?php echo toy_e($adminPageTitle); ?></h1>
        <nav>
            <a href="/admin">대시보드</a>
            <a href="/admin/settings">설정</a>
            <a href="/admin/members">회원</a>
            <form method="post" action="/logout" style="display:inline">
                <?php echo toy_csrf_field(); ?>
                <button type="submit">로그아웃</button>
            </form>
        </nav>
    </header>
    <main>
