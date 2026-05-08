<?php

declare(strict_types=1);

function toy_community_board_key_is_valid(string $boardKey): bool
{
    return preg_match('/\A[a-z][a-z0-9_]{1,59}\z/', $boardKey) === 1;
}

function toy_community_board_statuses(): array
{
    return ['enabled', 'disabled', 'archived'];
}

function toy_community_policy_values(string $policy): array
{
    if ($policy === 'read') {
        return ['public', 'member', 'group'];
    }

    if ($policy === 'write') {
        return ['member', 'group', 'admin'];
    }

    if ($policy === 'comment') {
        return ['member', 'group', 'disabled'];
    }

    return [];
}

function toy_community_boards(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, board_key, title, description, status, read_policy, write_policy, comment_policy, image_uploads_enabled, sort_order, created_at, updated_at
         FROM toy_community_boards
         ORDER BY sort_order ASC, id ASC'
    );

    return $stmt->fetchAll();
}

function toy_community_enabled_boards(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, board_key, title, description, status, read_policy, write_policy, comment_policy, image_uploads_enabled, sort_order, created_at, updated_at
         FROM toy_community_boards
         WHERE status = 'enabled'
         ORDER BY sort_order ASC, id ASC"
    );

    return $stmt->fetchAll();
}

function toy_community_board_by_key(PDO $pdo, string $boardKey): ?array
{
    if (!toy_community_board_key_is_valid($boardKey)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, board_key, title, description, status, read_policy, write_policy, comment_policy, image_uploads_enabled, sort_order, created_at, updated_at
         FROM toy_community_boards
         WHERE board_key = :board_key
         LIMIT 1'
    );
    $stmt->execute(['board_key' => $boardKey]);
    $board = $stmt->fetch();

    return is_array($board) ? $board : null;
}

function toy_community_board_by_id(PDO $pdo, int $boardId): ?array
{
    if ($boardId < 1) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, board_key, title, description, status, read_policy, write_policy, comment_policy, image_uploads_enabled, sort_order, created_at, updated_at
         FROM toy_community_boards
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $boardId]);
    $board = $stmt->fetch();

    return is_array($board) ? $board : null;
}

function toy_community_create_board(PDO $pdo, array $data): int
{
    $now = toy_now();
    $stmt = $pdo->prepare(
        'INSERT INTO toy_community_boards
            (board_key, title, description, status, read_policy, write_policy, comment_policy, image_uploads_enabled, sort_order, created_at, updated_at)
         VALUES
            (:board_key, :title, :description, :status, :read_policy, :write_policy, :comment_policy, :image_uploads_enabled, :sort_order, :created_at, :updated_at)'
    );
    $stmt->execute([
        'board_key' => (string) $data['board_key'],
        'title' => (string) $data['title'],
        'description' => (string) $data['description'],
        'status' => (string) $data['status'],
        'read_policy' => (string) $data['read_policy'],
        'write_policy' => (string) $data['write_policy'],
        'comment_policy' => (string) $data['comment_policy'],
        'image_uploads_enabled' => !empty($data['image_uploads_enabled']) ? 1 : 0,
        'sort_order' => (int) $data['sort_order'],
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return (int) $pdo->lastInsertId();
}

function toy_community_update_board(PDO $pdo, int $boardId, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE toy_community_boards
         SET title = :title,
             description = :description,
             status = :status,
             read_policy = :read_policy,
             write_policy = :write_policy,
             comment_policy = :comment_policy,
             image_uploads_enabled = :image_uploads_enabled,
             sort_order = :sort_order,
             updated_at = :updated_at
         WHERE id = :id'
    );
    $stmt->execute([
        'title' => (string) $data['title'],
        'description' => (string) $data['description'],
        'status' => (string) $data['status'],
        'read_policy' => (string) $data['read_policy'],
        'write_policy' => (string) $data['write_policy'],
        'comment_policy' => (string) $data['comment_policy'],
        'image_uploads_enabled' => !empty($data['image_uploads_enabled']) ? 1 : 0,
        'sort_order' => (int) $data['sort_order'],
        'updated_at' => toy_now(),
        'id' => $boardId,
    ]);
}

function toy_community_board_setting_value(PDO $pdo, int $boardId, string $settingKey): ?string
{
    if ($boardId < 1 || $settingKey === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT setting_value
         FROM toy_community_board_settings
         WHERE board_id = :board_id
           AND setting_key = :setting_key
         LIMIT 1'
    );
    $stmt->execute([
        'board_id' => $boardId,
        'setting_key' => $settingKey,
    ]);
    $value = $stmt->fetchColumn();

    return is_string($value) ? $value : null;
}

function toy_community_set_board_setting(PDO $pdo, int $boardId, string $settingKey, string $settingValue, string $valueType = 'string'): void
{
    if ($boardId < 1 || $settingKey === '') {
        return;
    }

    $now = toy_now();
    $stmt = $pdo->prepare(
        'INSERT INTO toy_community_board_settings
            (board_id, setting_key, setting_value, value_type, created_at, updated_at)
         VALUES
            (:board_id, :setting_key, :setting_value, :value_type, :created_at, :updated_at)
         ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            value_type = VALUES(value_type),
            updated_at = VALUES(updated_at)'
    );
    $stmt->execute([
        'board_id' => $boardId,
        'setting_key' => $settingKey,
        'setting_value' => $settingValue,
        'value_type' => $valueType,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function toy_community_board_attachment_max_bytes(PDO $pdo, int $boardId, array $settings = []): int
{
    $default = min(10485760, max(1024, (int) ($settings['attachment_max_bytes'] ?? 2097152)));
    $value = toy_community_board_setting_value($pdo, $boardId, 'attachment_max_bytes');
    if (!is_string($value) || $value === '') {
        return $default;
    }

    return min(10485760, max(1024, (int) $value));
}

function toy_community_board_attachment_max_count(PDO $pdo, int $boardId, array $settings = []): int
{
    $default = min(10, max(0, (int) ($settings['attachment_max_count'] ?? 1)));
    $value = toy_community_board_setting_value($pdo, $boardId, 'attachment_max_count');
    if (!is_string($value) || $value === '') {
        return $default;
    }

    return min(10, max(0, (int) $value));
}
