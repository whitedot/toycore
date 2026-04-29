<?php

$pageTitle = '회원가입';
$seo = [
    'title' => $pageTitle,
    'robots' => 'noindex, nofollow',
];
$popupLayerEnabled = isset($pdo) && $pdo instanceof PDO && toy_module_enabled($pdo, 'popup_layer');
if ($popupLayerEnabled) {
    require_once TOY_ROOT . '/modules/popup_layer/helpers.php';
}
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

        <?php if ($popupLayerEnabled) { ?>
            <?php echo toy_popup_layer_render($pdo, ['module_key' => 'member', 'point_key' => 'member.register']); ?>
        <?php } ?>

        <?php if ($errors !== []) { ?>
            <ul>
                <?php foreach ($errors as $error) { ?>
                    <li><?php echo toy_e($error); ?></li>
                <?php } ?>
            </ul>
        <?php } ?>

        <?php if ($registrationAllowed) { ?>
            <form method="post" action="<?php echo toy_e(toy_url('/register')); ?>">
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
        <?php } else { ?>
            <p>현재 회원가입을 사용할 수 없습니다.</p>
        <?php } ?>

        <p><a href="<?php echo toy_e(toy_url('/login')); ?>">로그인</a></p>
    </main>
</body>
</html>
