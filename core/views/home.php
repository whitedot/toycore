<?php

$pageTitle = isset($site['name']) ? (string) $site['name'] : 'Toycore';
$seo = [
    'title' => $pageTitle,
    'canonical' => toy_canonical_url($site, '/'),
];

if (isset($pdo) && $pdo instanceof PDO) {
    $seoSettings = toy_module_settings($pdo, 'seo');
    if (!empty($seoSettings['title_suffix']) && is_string($seoSettings['title_suffix'])) {
        $seo['title'] .= ' - ' . $seoSettings['title_suffix'];
    }
    if (!empty($seoSettings['default_description']) && is_string($seoSettings['default_description'])) {
        $seo['description'] = $seoSettings['default_description'];
    }
    if (!empty($seoSettings['default_og_image']) && is_string($seoSettings['default_og_image'])) {
        $seo['og'] = ['image' => $seoSettings['default_og_image']];
    }
}
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
        <p><a href="<?php echo toy_e(toy_url('/admin')); ?>">관리자 화면</a></p>
    </main>
</body>
</html>
