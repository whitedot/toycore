<?php

$pageTitle = '내 계정';
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
        <dl>
            <dt>이메일</dt>
            <dd><?php echo toy_e((string) $account['email']); ?></dd>
            <dt>표시 이름</dt>
            <dd><?php echo toy_e((string) $account['display_name']); ?></dd>
            <dt>상태</dt>
            <dd><?php echo toy_e((string) $account['status']); ?></dd>
            <dt>이메일 인증</dt>
            <dd><?php echo $account['email_verified_at'] === null ? '미인증' : toy_e((string) $account['email_verified_at']); ?></dd>
        </dl>

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

        <section>
            <h2>계정 정보</h2>
            <form method="post" action="/account">
                <?php echo toy_csrf_field(); ?>
                <input type="hidden" name="intent" value="basics">
                <p>
                    <label>표시 이름<br>
                        <input type="text" name="display_name" value="<?php echo toy_e((string) $account['display_name']); ?>" maxlength="120" required>
                    </label>
                </p>
                <p>
                    <label>선호 locale<br>
                        <input type="text" name="locale" value="<?php echo toy_e((string) $account['locale']); ?>" maxlength="20" required>
                    </label>
                </p>
                <button type="submit">계정 정보 저장</button>
            </form>
        </section>

        <section>
            <h2>이메일 인증</h2>
            <?php if ($account['email_verified_at'] === null) { ?>
                <form method="post" action="/account/email-verification">
                    <?php echo toy_csrf_field(); ?>
                    <button type="submit">인증 메일 다시 보내기</button>
                </form>
                <?php if ($emailVerificationUrl !== '') { ?>
                    <p><a href="<?php echo toy_e($emailVerificationUrl); ?>">이메일 인증 링크</a></p>
                <?php } ?>
            <?php } else { ?>
                <p>이메일 인증이 완료되었습니다.</p>
            <?php } ?>
        </section>

        <section>
            <h2>비밀번호 변경</h2>
            <form method="post" action="/account">
                <?php echo toy_csrf_field(); ?>
                <input type="hidden" name="intent" value="password">
                <p>
                    <label>현재 비밀번호<br>
                        <input type="password" name="current_password" required>
                    </label>
                </p>
                <p>
                    <label>새 비밀번호<br>
                        <input type="password" name="new_password" required>
                    </label>
                </p>
                <p>
                    <label>새 비밀번호 확인<br>
                        <input type="password" name="new_password_confirm" required>
                    </label>
                </p>
                <button type="submit">비밀번호 변경</button>
            </form>
        </section>

        <section>
            <h2>선택 프로필</h2>
            <form method="post" action="/account">
                <?php echo toy_csrf_field(); ?>
                <input type="hidden" name="intent" value="profile">
                <p>
                    <label>닉네임<br>
                        <input type="text" name="nickname" value="<?php echo toy_e($profile['nickname']); ?>" maxlength="80">
                    </label>
                </p>
                <p>
                    <label>전화번호<br>
                        <input type="text" name="phone" value="<?php echo toy_e($profile['phone']); ?>" maxlength="40">
                    </label>
                </p>
                <p>
                    <label>생년월일<br>
                        <input type="date" name="birth_date" value="<?php echo toy_e($profile['birth_date']); ?>">
                    </label>
                </p>
                <p>
                    <label>소개<br>
                        <textarea name="profile_text" maxlength="1000"><?php echo toy_e($profile['profile_text']); ?></textarea>
                    </label>
                </p>
                <button type="submit">프로필 저장</button>
            </form>
        </section>

        <section>
            <h2>동의 기록</h2>
            <?php if ($consents === []) { ?>
                <p>기록된 동의가 없습니다.</p>
            <?php } else { ?>
                <dl>
                    <?php foreach ($consents as $consent) { ?>
                        <dt><?php echo toy_e((string) $consent['consent_key']); ?></dt>
                        <dd>
                            <?php echo !empty($consent['consented']) ? '동의' : '미동의'; ?>
                            <?php echo toy_e((string) $consent['consent_version']); ?>
                            <?php echo toy_e((string) $consent['created_at']); ?>
                        </dd>
                    <?php } ?>
                </dl>
            <?php } ?>
        </section>

        <form method="post" action="/logout">
            <?php echo toy_csrf_field(); ?>
            <button type="submit">로그아웃</button>
        </form>
        <p><a href="/account/privacy-requests">개인정보 요청</a></p>
        <form method="post" action="/account/privacy-export">
            <?php echo toy_csrf_field(); ?>
            <button type="submit">개인정보 JSON 내보내기</button>
        </form>
        <p><a href="/account/withdraw">회원 탈퇴</a></p>
    </main>
</body>
</html>
