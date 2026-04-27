<?php

$pageTitle = '회원가입';
?>
<!doctype html>
<html lang="<?php echo toy_e(toy_locale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo toy_e($pageTitle); ?></title>
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

        <form method="post" action="/register">
            <?php echo toy_csrf_field(); ?>
            <p>
                <label>이메일<br>
                    <input type="email" name="email" value="<?php echo toy_e($values['email']); ?>" required>
                </label>
            </p>
            <p>
                <label>표시 이름<br>
                    <input type="text" name="display_name" value="<?php echo toy_e($values['display_name']); ?>" maxlength="120" required>
                </label>
            </p>
            <p>
                <label>비밀번호<br>
                    <input type="password" name="password" required>
                </label>
            </p>
            <p>
                <label>비밀번호 확인<br>
                    <input type="password" name="password_confirm" required>
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="terms_consent" value="1" required>
                    필수 약관에 동의합니다.
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="privacy_consent" value="1" required>
                    개인정보 처리방침에 동의합니다.
                </label>
            </p>
            <button type="submit">가입</button>
        </form>
        <p><a href="/login">로그인</a></p>
    </main>
</body>
</html>
