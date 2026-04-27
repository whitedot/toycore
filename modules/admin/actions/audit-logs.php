<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$filters = [
    'event_type' => toy_get_string('event_type', 80),
    'target_type' => toy_get_string('target_type', 60),
    'result' => toy_get_string('result', 30),
];

$where = [];
$params = [];

if ($filters['event_type'] !== '') {
    $where[] = 'event_type = :event_type';
    $params['event_type'] = $filters['event_type'];
}

if ($filters['target_type'] !== '') {
    $where[] = 'target_type = :target_type';
    $params['target_type'] = $filters['target_type'];
}

if ($filters['result'] !== '') {
    $where[] = 'result = :result';
    $params['result'] = $filters['result'];
}

$sql = 'SELECT id, actor_account_id, actor_type, event_type, target_type, target_id, result, ip_address, message, metadata_json, created_at
        FROM toy_audit_logs';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$logs = [];
foreach ($stmt->fetchAll() as $row) {
    $logs[] = $row;
}

include TOY_ROOT . '/modules/admin/views/audit-logs.php';
