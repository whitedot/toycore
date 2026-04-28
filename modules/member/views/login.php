<?php

$pageTitle = '로그인';
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

        <?php if ($errors !== []) { ?>
            <ul>
                <?php foreach ($errors as $error) { ?>
                    <li><?php echo toy_e($error); ?></li>
                <?php } ?>
            </ul>
        <?php } ?>

        <form method="post" action="/login">
            <?php echo toy_csrf_field(); ?>
            <input type="hidden" name="next" value="<?php echo toy_e($next); ?>">
            <p>
                <label>이메일<br>
                    <input type="email" name="identifier" value="<?php echo toy_e($identifier); ?>" required>
                </label>
            </p>
            <p>
                <label>비밀번호<br>
                    <input type="password" name="password" required>
                </label>
            </p>
            <button type="submit">로그인</button>
        </form>
        <p><a href="/register">회원가입</a></p>
        <p><a href="/password/reset">비밀번호 재설정</a></p>
    </main>
</body>
</html>
