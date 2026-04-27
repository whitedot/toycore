<?php

$pageTitle = '로그인';
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo toy_e($pageTitle); ?></title>
    <meta name="robots" content="noindex">
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
    </main>
</body>
</html>
