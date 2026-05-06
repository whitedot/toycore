<?php

declare(strict_types=1);

function toy_admin_audit_log_filters(): array
{
    return [
        'event_type' => toy_get_string('event_type', 80),
        'target_type' => toy_get_string('target_type', 60),
        'actor_account_id' => toy_get_string('actor_account_id', 20),
        'result' => toy_get_string('result', 30),
        'date_from' => toy_get_string('date_from', 30),
        'date_to' => toy_get_string('date_to', 30),
    ];
}

function toy_admin_audit_log_identifier_filter(string $value, int $maxLength): string
{
    if ($value === '' || strlen($value) > $maxLength) {
        return '';
    }

    return preg_match('/\A[a-z][a-z0-9_.-]*\z/', $value) === 1 ? $value : '';
}

function toy_admin_audit_log_result_filter(string $value): string
{
    return in_array($value, ['success', 'failure'], true) ? $value : '';
}

function toy_admin_audit_log_date_filter(string $value): string
{
    if ($value === '' || preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value) !== 1) {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        !$date instanceof DateTimeImmutable
        || (is_array($dateErrors) && ((int) $dateErrors['warning_count'] > 0 || (int) $dateErrors['error_count'] > 0))
    ) {
        return '';
    }

    return $date->format('Y-m-d') === $value ? $value : '';
}

function toy_admin_audit_metadata_redact(mixed $value, string $key = ''): mixed
{
    if ($key !== '' && toy_admin_setting_value_is_secret($key)) {
        return $value === '' ? '' : '[masked]';
    }

    if (is_string($value)) {
        return toy_log_sensitive_text_sanitize($value);
    }

    if (!is_array($value)) {
        return $value;
    }

    $redacted = [];
    foreach ($value as $childKey => $childValue) {
        $redacted[$childKey] = toy_admin_audit_metadata_redact($childValue, is_string($childKey) ? $childKey : '');
    }

    return $redacted;
}

function toy_admin_audit_log_display_metadata(array $log): string
{
    $metadataJson = (string) ($log['metadata_json'] ?? '');
    if ($metadataJson === '') {
        return '';
    }

    $metadata = json_decode($metadataJson, true);
    if (!is_array($metadata)) {
        return '[invalid metadata]';
    }

    $encoded = json_encode(toy_admin_audit_metadata_redact($metadata), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    return is_string($encoded) ? $encoded : '[invalid metadata]';
}

function toy_admin_audit_log_display_message(array $log): string
{
    return toy_log_sensitive_text_sanitize(toy_log_line_value((string) ($log['message'] ?? ''), 1000));
}

function toy_admin_audit_log_query_parts(array &$filters): array
{
    $where = [];
    $params = [];
    $filters['event_type'] = toy_admin_audit_log_identifier_filter($filters['event_type'], 80);
    $filters['target_type'] = toy_admin_audit_log_identifier_filter($filters['target_type'], 60);
    $filters['result'] = toy_admin_audit_log_result_filter($filters['result']);
    $filters['date_from'] = toy_admin_audit_log_date_filter($filters['date_from']);
    $filters['date_to'] = toy_admin_audit_log_date_filter($filters['date_to']);

    if ($filters['event_type'] !== '') {
        $where[] = 'event_type = :event_type';
        $params['event_type'] = $filters['event_type'];
    }

    if ($filters['target_type'] !== '') {
        $where[] = 'target_type = :target_type';
        $params['target_type'] = $filters['target_type'];
    }

    if ($filters['actor_account_id'] !== '') {
        if (ctype_digit($filters['actor_account_id'])) {
            $where[] = 'actor_account_id = :actor_account_id';
            $params['actor_account_id'] = (int) $filters['actor_account_id'];
        } else {
            $filters['actor_account_id'] = '';
        }
    }

    if ($filters['result'] !== '') {
        $where[] = 'result = :result';
        $params['result'] = $filters['result'];
    }

    if ($filters['date_from'] !== '') {
        $dateFrom = DateTimeImmutable::createFromFormat('!Y-m-d', $filters['date_from']);
        if ($dateFrom instanceof DateTimeImmutable) {
            $where[] = 'created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d 00:00:00');
        }
    }

    if ($filters['date_to'] !== '') {
        $dateTo = DateTimeImmutable::createFromFormat('!Y-m-d', $filters['date_to']);
        if ($dateTo instanceof DateTimeImmutable) {
            $where[] = 'created_at <= :date_to';
            $params['date_to'] = $dateTo->format('Y-m-d 23:59:59');
        }
    }

    return [
        'where' => $where,
        'params' => $params,
    ];
}

function toy_admin_audit_logs(PDO $pdo, array &$filters): array
{
    $queryParts = toy_admin_audit_log_query_parts($filters);
    $where = $queryParts['where'];
    $params = $queryParts['params'];

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

    return $logs;
}
