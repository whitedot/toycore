<?php

declare(strict_types=1);

function toy_community_notification_available(PDO $pdo): bool
{
    if (!function_exists('toy_module_enabled') || !toy_module_enabled($pdo, 'notification')) {
        return false;
    }

    $helperPath = TOY_ROOT . '/modules/notification/helpers.php';
    if (!is_file($helperPath)) {
        return false;
    }

    require_once $helperPath;

    return function_exists('toy_notification_create');
}

function toy_community_create_account_notification(
    PDO $pdo,
    int $accountId,
    string $title,
    string $bodyText,
    string $linkUrl,
    ?int $createdByAccountId = null
): void {
    if ($accountId < 1 || !toy_community_notification_available($pdo)) {
        return;
    }

    try {
        toy_notification_create($pdo, [
            'audience' => 'account',
            'account_id' => $accountId,
            'title' => $title,
            'body_text' => $bodyText,
            'link_url' => $linkUrl,
            'channels' => ['site'],
            'created_by_account_id' => $createdByAccountId,
        ]);
    } catch (Throwable $exception) {
        toy_log_exception($exception, 'community_notification_create');
    }
}
