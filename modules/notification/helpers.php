<?php

declare(strict_types=1);

function toy_notification_clean_single_line(string $value, int $maxLength): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function toy_notification_clean_text(string $value, int $maxLength): string
{
    $value = trim($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function toy_notification_clean_link_url(string $value): string
{
    $value = trim($value);
    if ($value === '' || toy_is_safe_relative_url($value) || toy_is_http_url($value)) {
        return $value;
    }

    return '';
}

function toy_notification_create(PDO $pdo, array $data): int
{
    $audience = (string) ($data['audience'] ?? 'account');
    if (!in_array($audience, ['account', 'all'], true)) {
        throw new InvalidArgumentException('Notification audience is invalid.');
    }

    $accountId = isset($data['account_id']) && (int) $data['account_id'] > 0 ? (int) $data['account_id'] : null;
    if ($audience === 'account' && $accountId === null) {
        throw new InvalidArgumentException('Account notification requires account_id.');
    }

    $title = toy_notification_clean_single_line((string) ($data['title'] ?? ''), 160);
    if ($title === '') {
        throw new InvalidArgumentException('Notification title is required.');
    }

    $bodyText = toy_notification_clean_text((string) ($data['body_text'] ?? ''), 5000);
    $linkUrl = toy_notification_clean_link_url((string) ($data['link_url'] ?? ''));
    $createdByAccountId = isset($data['created_by_account_id']) && (int) $data['created_by_account_id'] > 0
        ? (int) $data['created_by_account_id']
        : null;

    $startedTransaction = !$pdo->inTransaction();
    if ($startedTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $now = toy_now();
        $stmt = $pdo->prepare(
            'INSERT INTO toy_notifications
                (account_id, audience, title, body_text, link_url, status, read_at, created_by_account_id, created_at, updated_at)
             VALUES
                (:account_id, :audience, :title, :body_text, :link_url, :status, NULL, :created_by_account_id, :created_at, :updated_at)'
        );
        $stmt->execute([
            'account_id' => $accountId,
            'audience' => $audience,
            'title' => $title,
            'body_text' => $bodyText,
            'link_url' => $linkUrl,
            'status' => 'queued',
            'created_by_account_id' => $createdByAccountId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $notificationId = (int) $pdo->lastInsertId();
        $channels = isset($data['channels']) && is_array($data['channels']) ? $data['channels'] : ['site'];
        toy_notification_queue_deliveries($pdo, $notificationId, $channels, (string) ($data['recipient'] ?? ''));

        if ($startedTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }

    return $notificationId;
}

function toy_notification_queue_deliveries(PDO $pdo, int $notificationId, array $channels, string $recipient): void
{
    $allowedChannels = ['site', 'email', 'sms', 'alimtalk'];
    $now = toy_now();
    $stmt = $pdo->prepare(
        'INSERT INTO toy_notification_deliveries
            (notification_id, channel, recipient, status, provider_message_id, error_message, attempted_at, created_at, updated_at)
         VALUES
            (:notification_id, :channel, :recipient, :status, :provider_message_id, :error_message, NULL, :created_at, :updated_at)'
    );

    foreach ($channels as $channel) {
        $channel = is_string($channel) ? $channel : '';
        if (!in_array($channel, $allowedChannels, true)) {
            continue;
        }

        $stmt->execute([
            'notification_id' => $notificationId,
            'channel' => $channel,
            'recipient' => $channel === 'site' ? '' : $recipient,
            'status' => $channel === 'site' ? 'ready' : 'queued',
            'provider_message_id' => '',
            'error_message' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
