<?php

$pageTitle = 'Toycore 설치';
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo toy_e($pageTitle); ?></title>
</head>
<body>
    <main>
        <h1><?php echo toy_e($pageTitle); ?></h1>

        <?php if ($errors !== []) { ?>
            <section>
                <h2>확인 필요</h2>
                <ul>
                    <?php foreach ($errors as $error) { ?>
                        <li><?php echo toy_e($error); ?></li>
                    <?php } ?>
                </ul>
            </section>
        <?php } ?>

        <form method="post" action="/">
            <?php echo toy_csrf_field(); ?>

            <fieldset>
                <legend>DB 정보</legend>
                <p>
                    <label>DB host<br>
                        <input type="text" name="db_host" value="<?php echo toy_e($values['db_host']); ?>" required>
                    </label>
                </p>
                <p>
                    <label>DB name<br>
                        <input type="text" name="db_name" value="<?php echo toy_e($values['db_name']); ?>" required>
                    </label>
                </p>
                <p>
                    <label>DB user<br>
                        <input type="text" name="db_user" value="<?php echo toy_e($values['db_user']); ?>" required>
                    </label>
                </p>
                <p>
                    <label>DB password<br>
                        <input type="password" name="db_password" value="<?php echo toy_e($values['db_password']); ?>">
                    </label>
                </p>
            </fieldset>

            <fieldset>
                <legend>사이트 정보</legend>
                <p>
                    <label>사이트 이름<br>
                        <input type="text" name="site_name" value="<?php echo toy_e($values['site_name']); ?>" required>
                    </label>
                </p>
                <p>
                    <label>기본 URL<br>
                        <input type="url" name="base_url" value="<?php echo toy_e($values['base_url']); ?>">
                    </label>
                </p>
                <p>
                    <label>timezone<br>
                        <input type="text" name="timezone" value="<?php echo toy_e($values['timezone']); ?>" required>
                    </label>
                </p>
                <p>
                    <label>기본 locale<br>
                        <input type="text" name="default_locale" value="<?php echo toy_e($values['default_locale']); ?>" required>
                    </label>
                </p>
            </fieldset>

            <fieldset>
                <legend>최초 관리자</legend>
                <p>
                    <label>이메일<br>
                        <input type="email" name="admin_email" value="<?php echo toy_e($values['admin_email']); ?>" required>
                    </label>
                </p>
                <p>
                    <label>표시 이름<br>
                        <input type="text" name="admin_display_name" value="<?php echo toy_e($values['admin_display_name']); ?>" required>
                    </label>
                </p>
                <p>
                    <label>비밀번호<br>
                        <input type="password" name="admin_password" required>
                    </label>
                </p>
                <p>
                    <label>비밀번호 확인<br>
                        <input type="password" name="admin_password_confirm" required>
                    </label>
                </p>
            </fieldset>

            <button type="submit">설치</button>
        </form>
    </main>
</body>
</html>
