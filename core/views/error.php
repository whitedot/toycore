<!doctype html>
<html lang="<?php echo toy_e(toy_locale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php echo toy_seo_tags([
        'title' => $pageTitle,
        'robots' => 'noindex, nofollow',
    ], null); ?>
    <?php echo toy_stylesheet_tag(); ?>
</head>
<body>
    <main>
        <h1><?php echo toy_e($pageTitle); ?></h1>
        <p><?php echo toy_e($message); ?></p>

        <?php if (!empty($debug) && $exception instanceof Throwable) { ?>
            <pre><?php echo toy_e($exception->getMessage()); ?></pre>
        <?php } ?>
    </main>
</body>
</html>
