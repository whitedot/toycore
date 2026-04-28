<?php

$pageTitle = isset($site['name']) ? (string) $site['name'] : 'Toycore';
$seo = [
    'title' => $pageTitle,
    'canonical' => toy_canonical_url($site, '/'),
];
?>
<!doctype html>
<html lang="<?php echo toy_e(toy_locale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php echo toy_seo_tags($seo, $site); ?>
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
