<?php

$pageTitle = isset($site['name']) ? (string) $site['name'] : 'Toycore';
?>
<!doctype html>
<html lang="<?php echo toy_e((string) ($site['default_locale'] ?? 'ko')); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo toy_e($pageTitle); ?></title>
    <?php echo toy_stylesheet_tag(); ?>
</head>
<body>
    <main>
        <h1><?php echo toy_e($pageTitle); ?></h1>
        <p>Toycore MVP가 설치되었습니다.</p>
        <p><a href="/admin">관리자 화면</a></p>
    </main>
</body>
</html>
