<?php

$pageTitle = '이메일 인증 완료';
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo toy_e($pageTitle); ?></title>
    <?php echo toy_stylesheet_tag(); ?>
</head>
<body>
    <main>
        <h1><?php echo toy_e($pageTitle); ?></h1>
        <p>이메일 인증을 완료했습니다.</p>
        <p><a href="/account">내 계정</a></p>
    </main>
</body>
</html>
