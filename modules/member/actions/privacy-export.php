<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';

$account = toy_member_require_login($pdo);
$export = toy_member_privacy_export_data($pdo, (int) $account['id']);

toy_audit_log($pdo, [
    'actor_account_id' => (int) $account['id'],
    'actor_type' => 'member',
    'event_type' => 'privacy.export.downloaded',
    'target_type' => 'member_account',
    'target_id' => (string) $account['id'],
    'result' => 'success',
    'message' => 'Member privacy export downloaded.',
]);

header('Content-Type: application/json; charset=UTF-8');
header('Content-Disposition: attachment; filename="toycore-privacy-export-' . (int) $account['id'] . '.json"');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
