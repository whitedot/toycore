<?php

declare(strict_types=1);

function toy_community_attachment_for_read(PDO $pdo, int $attachmentId, ?array $account): ?array
{
    if ($attachmentId < 1) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT id, post_id, uploader_account_id, original_name, stored_name, storage_path, mime_type, size_bytes, checksum_sha256, width, height, status, created_at
         FROM toy_community_attachments
         WHERE id = :id
           AND status = 'active'
         LIMIT 1"
    );
    $stmt->execute(['id' => $attachmentId]);
    $attachment = $stmt->fetch();
    if (!is_array($attachment)) {
        return null;
    }

    $post = toy_community_post_for_read($pdo, (int) $attachment['post_id'], $account);
    if (!is_array($post)) {
        return null;
    }

    $attachment['post'] = $post;
    return $attachment;
}

function toy_community_attachment_mime_is_allowed(string $mimeType): bool
{
    return in_array(strtolower(trim($mimeType)), [
        'image/jpeg',
        'image/png',
        'image/webp',
    ], true);
}

function toy_community_attachment_file_path(array $attachment): ?string
{
    $storageRoot = realpath(TOY_ROOT . '/storage');
    if (!is_string($storageRoot) || !is_dir($storageRoot)) {
        return null;
    }

    $storagePath = (string) ($attachment['storage_path'] ?? '');
    if ($storagePath === '' || str_contains($storagePath, "\0")) {
        return null;
    }

    $candidate = str_starts_with($storagePath, DIRECTORY_SEPARATOR)
        ? $storagePath
        : TOY_ROOT . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storagePath), DIRECTORY_SEPARATOR);
    $realPath = realpath($candidate);
    if (!is_string($realPath) && !str_starts_with($storagePath, DIRECTORY_SEPARATOR)) {
        $fallback = $storageRoot . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storagePath), DIRECTORY_SEPARATOR);
        $realPath = realpath($fallback);
    }
    if (!is_string($realPath) || !is_file($realPath)) {
        return null;
    }

    $storagePrefix = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($realPath, $storagePrefix)) {
        return null;
    }

    return $realPath;
}
