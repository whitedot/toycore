<?php

$adminPageTitle = '회원 설정';
include TOY_ROOT . '/modules/admin/views/layout-header.php';
?>

<?php if ($notice !== '') { ?>
    <p><?php echo toy_e($notice); ?></p>
<?php } ?>

<?php if ($errors !== []) { ?>
    <ul>
        <?php foreach ($errors as $error) { ?>
            <li><?php echo toy_e($error); ?></li>
        <?php } ?>
    </ul>
<?php } ?>

<form method="post" action="/admin/member-settings">
    <?php echo toy_csrf_field(); ?>

    <section>
        <h2>가입과 인증</h2>
        <p>
            <label>
                <input type="checkbox" name="allow_registration" value="1"<?php echo !empty($settings['allow_registration']) ? ' checked' : ''; ?>>
                공개 회원가입 허용
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="email_verification_enabled" value="1"<?php echo !empty($settings['email_verification_enabled']) ? ' checked' : ''; ?>>
                이메일 인증 사용
            </label>
        </p>
    </section>

    <section>
        <h2>로그인 시도 제한</h2>
        <p>
            <label>제한 시간(초)<br>
                <input type="number" name="login_throttle_window_seconds" value="<?php echo toy_e((string) $settings['login_throttle_window_seconds']); ?>" min="0" max="86400">
            </label>
        </p>
        <p>
            <label>계정 기준 제한 횟수<br>
                <input type="number" name="login_throttle_account_limit" value="<?php echo toy_e((string) $settings['login_throttle_account_limit']); ?>" min="0" max="1000">
            </label>
        </p>
        <p>
            <label>IP 기준 제한 횟수<br>
                <input type="number" name="login_throttle_ip_limit" value="<?php echo toy_e((string) $settings['login_throttle_ip_limit']); ?>" min="0" max="1000">
            </label>
        </p>
    </section>

    <section>
        <h2>회원가입 제한</h2>
        <p>
            <label>제한 시간(초)<br>
                <input type="number" name="register_throttle_window_seconds" value="<?php echo toy_e((string) $settings['register_throttle_window_seconds']); ?>" min="0" max="86400">
            </label>
        </p>
        <p>
            <label>IP 기준 제한 횟수<br>
                <input type="number" name="register_throttle_ip_limit" value="<?php echo toy_e((string) $settings['register_throttle_ip_limit']); ?>" min="0" max="1000">
            </label>
        </p>
    </section>

    <section>
        <h2>비밀번호 재설정 제한</h2>
        <p>
            <label>제한 시간(초)<br>
                <input type="number" name="password_reset_throttle_window_seconds" value="<?php echo toy_e((string) $settings['password_reset_throttle_window_seconds']); ?>" min="0" max="86400">
            </label>
        </p>
        <p>
            <label>계정 기준 제한 횟수<br>
                <input type="number" name="password_reset_throttle_account_limit" value="<?php echo toy_e((string) $settings['password_reset_throttle_account_limit']); ?>" min="0" max="1000">
            </label>
        </p>
        <p>
            <label>IP 기준 제한 횟수<br>
                <input type="number" name="password_reset_throttle_ip_limit" value="<?php echo toy_e((string) $settings['password_reset_throttle_ip_limit']); ?>" min="0" max="1000">
            </label>
        </p>
    </section>

    <section>
        <h2>이메일 인증 제한</h2>
        <p>
            <label>제한 시간(초)<br>
                <input type="number" name="email_verification_throttle_window_seconds" value="<?php echo toy_e((string) $settings['email_verification_throttle_window_seconds']); ?>" min="0" max="86400">
            </label>
        </p>
        <p>
            <label>계정 기준 제한 횟수<br>
                <input type="number" name="email_verification_throttle_account_limit" value="<?php echo toy_e((string) $settings['email_verification_throttle_account_limit']); ?>" min="0" max="1000">
            </label>
        </p>
        <p>
            <label>IP 기준 제한 횟수<br>
                <input type="number" name="email_verification_throttle_ip_limit" value="<?php echo toy_e((string) $settings['email_verification_throttle_ip_limit']); ?>" min="0" max="1000">
            </label>
        </p>
    </section>

    <button type="submit">저장</button>
</form>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
