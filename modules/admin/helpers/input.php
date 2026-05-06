<?php

declare(strict_types=1);

function toy_admin_post_positive_int(string $key, int $maxLength = 20): int
{
    $value = $_POST[$key] ?? '';
    if (is_array($value)) {
        return 0;
    }

    $value = trim((string) $value);
    if ($value === '' || strlen($value) > $maxLength || preg_match('/\A[1-9][0-9]*\z/', $value) !== 1) {
        return 0;
    }

    return (int) $value;
}
