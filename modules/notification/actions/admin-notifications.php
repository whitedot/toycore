<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';
require_once TOY_ROOT . '/modules/notification/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$allowedAudiences = ['account', 'all'];
$allowedChannels = ['site', 'email', 'sms', 'alimtalk'];
$errors = [];
$notice = '';

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $audience = toy_post_string('audience', 30);
    $accountId = (int) toy_post_string('account_id', 20);
    $title = toy_notification_clean_single_line(toy_post_string('title', 160), 160);
    $bodyText = toy_notification_clean_text(toy_post_string('body_text', 5000), 5000);
    $linkUrl = toy_notification_clean_link_url(toy_post_string('link_url', 255));
    $recipient = toy_notification_clean_single_line(toy_post_string('recipient', 255), 255);
    $postedChannels = $_POST['channels'] ?? [];
    $channels = [];

    if (!in_array($audience, $allowedAudiences, true)) {
        $errors[] = '알림 대상을 선택하세요.';
    }
    if ($audience === 'account' && $accountId <= 0) {
        $errors[] = '회원 ID를 입력하세요.';
    }
    if ($title === '') {
        $errors[] = '제목을 입력하세요.';
    }
    if (!is_array($postedChannels)) {
        $errors[] = '발송 채널 값이 올바르지 않습니다.';
    } else {
        foreach ($postedChannels as $channel) {
            $channel = is_string($channel) ? $channel : '';
            if (in_array($channel, $allowedChannels, true)) {
                $channels[$channel] = $channel;
            }
        }
    }
    if ($channels === []) {
        $errors[] = '발송 채널을 하나 이상 선택하세요.';
    }

    if ($errors === []) {
        try {
            $notificationId = toy_notification_create($pdo, [
                'audience' => $audience,
                'account_id' => $audience === 'account' ? $accountId : null,
                'title' => $title,
                'body_text' => $bodyText,
                'link_url' => $linkUrl,
                'channels' => array_values($channels),
                'recipient' => $recipient,
                'created_by_account_id' => (int) $account['id'],
            ]);

            toy_audit_log($pdo, [
                'actor_account_id' => (int) $account['id'],
                'actor_type' => 'admin',
                'event_type' => 'notification.created',
                'target_type' => 'notification',
                'target_id' => (string) $notificationId,
                'result' => 'success',
                'message' => 'Notification created.',
                'metadata' => [
                    'audience' => $audience,
                    'channels' => array_values($channels),
                ],
            ]);

            $notice = '알림을 등록했습니다. 이메일/SMS/알림톡은 발송 대기열에 쌓입니다.';
        } catch (Throwable $exception) {
            $errors[] = '알림 등록 중 오류가 발생했습니다.';
        }
    }
}

$notifications = [];
$stmt = $pdo->query(
    'SELECT id, account_id, audience, title, status, read_at, created_by_account_id, created_at
     FROM toy_notifications
     ORDER BY id DESC
     LIMIT 100'
);
foreach ($stmt->fetchAll() as $row) {
    $notifications[] = $row;
}

$deliveries = [];
$stmt = $pdo->query(
    'SELECT d.id, d.notification_id, d.channel, d.recipient, d.status, d.error_message, d.updated_at
     FROM toy_notification_deliveries d
     ORDER BY d.id DESC
     LIMIT 100'
);
foreach ($stmt->fetchAll() as $row) {
    $deliveries[] = $row;
}

include TOY_ROOT . '/modules/notification/views/admin-notifications.php';
