<?php

$adminPageTitle = $adminPageTitle ?? '관리자';
$seo = [
    'title' => $adminPageTitle,
    'robots' => 'noindex, nofollow',
];
$adminNavigationGroups = isset($pdo) && $pdo instanceof PDO ? toy_admin_navigation_groups($pdo) : [];
?>
<!doctype html>
<html lang="<?php echo toy_e(toy_locale()); ?>" data-color-scheme="<?php echo toy_e(toy_color_scheme($site ?? null)); ?>">
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
            <?php foreach ($adminNavigationGroups as $adminNavigationGroup) { ?>
                <details>
                    <summary><?php echo toy_e((string) $adminNavigationGroup['label']); ?></summary>
                    <?php foreach ($adminNavigationGroup['items'] as $adminNavigationItem) { ?>
                        <a href="<?php echo toy_e(toy_url((string) $adminNavigationItem['path'])); ?>"><?php echo toy_e($adminNavigationItem['label']); ?></a>
                    <?php } ?>
                </details>
            <?php } ?>
            <form method="post" action="<?php echo toy_e(toy_url('/logout')); ?>" style="display:inline">
                <?php echo toy_csrf_field(); ?>
                <button type="submit">로그아웃</button>
            </form>
        </nav>
    </header>
    <main>
