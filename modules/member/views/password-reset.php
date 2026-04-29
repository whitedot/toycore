<?php

$pageTitle = '새 비밀번호 설정';
$seo = [
    'title' => $pageTitle,
    'robots' => 'noindex, nofollow',
];
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
    <main>
        <h1><?php echo toy_e($pageTitle); ?></h1>

        <?php if ($notice !== '') { ?>
            <p><?php echo toy_e($notice); ?></p>
            <p><a href="<?php echo toy_e(toy_url('/login')); ?>">로그인</a></p>
        <?php } else { ?>
            <?php if ($errors !== []) { ?>
                <ul>
                    <?php foreach ($errors as $error) { ?>
                        <li><?php echo toy_e($error); ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>

            <form method="post" action="<?php echo toy_e(toy_url('/password/reset/confirm')); ?>">
                <?php echo toy_csrf_field(); ?>
                <input type="hidden" name="token" value="<?php echo toy_e($token); ?>">
                <p>
                    <label>새 비밀번호<br>
                        <input type="password" name="password" required>
                    </label>
                </p>
                <p>
                    <label>새 비밀번호 확인<br>
                        <input type="password" name="password_confirm" required>
                    </label>
                </p>
                <button type="submit">비밀번호 재설정</button>
            </form>
        <?php } ?>
    </main>
</body>
</html>
