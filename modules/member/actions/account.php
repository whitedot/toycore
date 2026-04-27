<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$account = toy_member_require_login($pdo);
$errors = [];
$notice = '';
$emailVerificationUrl = '';
$submittedProfile = null;

if (!empty($config['debug']) && !empty($_SESSION['toy_debug_email_verification_url']) && is_string($_SESSION['toy_debug_email_verification_url'])) {
    $emailVerificationUrl = $_SESSION['toy_debug_email_verification_url'];
}

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $intent = toy_post_string('intent', 40);

    if ($intent === 'profile') {
        $profile = [
            'nickname' => toy_post_string('nickname', 80),
            'phone' => toy_post_string('phone', 40),
            'birth_date' => toy_post_string('birth_date', 10),
            'profile_text' => toy_post_string('profile_text', 1000),
        ];
        $submittedProfile = $profile;

        if ($profile['birth_date'] !== '' && preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $profile['birth_date']) !== 1) {
            $errors[] = '생년월일은 YYYY-MM-DD 형식으로 입력하세요.';
        } elseif ($profile['birth_date'] !== '') {
            $birthParts = explode('-', $profile['birth_date']);
            if (!checkdate((int) $birthParts[1], (int) $birthParts[2], (int) $birthParts[0])) {
                $errors[] = '생년월일이 올바르지 않습니다.';
            }
        }

        if ($errors === []) {
            toy_member_save_profile($pdo, (int) $account['id'], $profile);
            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'member',
                'event_type' => 'member.profile.updated',
                'target_type' => 'member_account',
                'target_id' => (string) $account['id'],
                'result' => 'success',
                'message' => 'Member profile updated.',
            ]);
            $notice = '프로필을 저장했습니다.';
        }
    } else {
        $currentPassword = toy_post_string('current_password', 255);
        $newPassword = toy_post_string('new_password', 255);
        $newPasswordConfirm = toy_post_string('new_password_confirm', 255);

        if (!password_verify($currentPassword, (string) $account['password_hash'])) {
            $errors[] = '현재 비밀번호가 올바르지 않습니다.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = '새 비밀번호는 8자 이상이어야 합니다.';
        }

        if ($newPassword !== $newPasswordConfirm) {
            $errors[] = '새 비밀번호 확인이 일치하지 않습니다.';
        }

        if ($errors === []) {
            toy_member_update_password($pdo, (int) $account['id'], $newPassword);
            toy_member_log_auth($pdo, (int) $account['id'], 'password_change', 'success');
            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'member',
                'event_type' => 'member.password.changed',
                'target_type' => 'member_account',
                'target_id' => (string) $account['id'],
                'result' => 'success',
                'message' => 'Member password changed.',
            ]);

            $account = toy_member_current_account($pdo);
            $notice = '비밀번호를 변경했습니다.';
        } else {
            toy_member_log_auth($pdo, (int) $account['id'], 'password_change', 'failure');
        }
    }
}

$profile = toy_member_profile($pdo, (int) $account['id']);
if (is_array($submittedProfile) && $errors !== []) {
    $profile = array_merge($profile, $submittedProfile);
}
$consents = toy_member_latest_consents($pdo, (int) $account['id']);

include TOY_ROOT . '/modules/member/views/account.php';
