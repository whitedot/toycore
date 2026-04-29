<?php

declare(strict_types=1);

function toy_banner_clean_single_line(string $value, int $maxLength): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function toy_banner_clean_text(string $value, int $maxLength): string
{
    $value = trim($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function toy_banner_clean_url(string $value): string
{
    $value = trim($value);
    if ($value === '' || toy_is_safe_relative_url($value) || toy_is_http_url($value)) {
        return $value;
    }

    return '';
}

function toy_banner_clean_admin_datetime(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
    return $date instanceof DateTimeImmutable ? $date->format('Y-m-d H:i:00') : null;
}

function toy_banner_admin_datetime_value(mixed $value): string
{
    if (!is_string($value) || $value === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
    return $date instanceof DateTimeImmutable ? $date->format('Y-m-d\TH:i') : '';
}

function toy_banner_available_targets(PDO $pdo): array
{
    $targets = [
        [
            'module_key' => 'core',
            'point_key' => 'site.home',
            'slot_key' => 'before_content',
            'label' => '홈 / 본문 위',
        ],
        [
            'module_key' => 'core',
            'point_key' => 'site.home',
            'slot_key' => 'after_content',
            'label' => '홈 / 본문 아래',
        ],
    ];

    foreach (toy_enabled_module_contract_files($pdo, 'extension-points.php', []) as $moduleKey => $file) {
        $points = include $file;
        if (!is_array($points)) {
            continue;
        }

        foreach ($points as $point) {
            if (!is_array($point) || empty($point['output'])) {
                continue;
            }

            $pointKey = (string) ($point['point_key'] ?? '');
            if (preg_match('/\A[a-z0-9][a-z0-9_.-]{0,119}\z/', $pointKey) !== 1) {
                continue;
            }

            $pointLabel = (string) ($point['label'] ?? $pointKey);
            $slots = isset($point['slots']) && is_array($point['slots']) ? $point['slots'] : [];
            foreach ($slots as $slot) {
                if (!is_array($slot) || (string) ($slot['kind'] ?? '') !== 'content') {
                    continue;
                }

                $slotKey = (string) ($slot['slot_key'] ?? '');
                if (preg_match('/\A[a-z0-9][a-z0-9_.-]{0,79}\z/', $slotKey) !== 1) {
                    continue;
                }

                $targets[] = [
                    'module_key' => $moduleKey,
                    'point_key' => $pointKey,
                    'slot_key' => $slotKey,
                    'label' => $moduleKey . ' / ' . $pointLabel . ' / ' . (string) ($slot['label'] ?? $slotKey),
                ];
            }
        }
    }

    return $targets;
}

function toy_banner_target_option_value(array $target): string
{
    return (string) $target['module_key'] . '|' . (string) $target['point_key'] . '|' . (string) $target['slot_key'];
}

function toy_banner_find_target(array $targets, string $option): ?array
{
    foreach ($targets as $target) {
        if (toy_banner_target_option_value($target) === $option) {
            return $target;
        }
    }

    return null;
}

function toy_banner_render_slot(PDO $pdo, array $context): string
{
    $moduleKey = (string) ($context['module_key'] ?? '');
    $pointKey = (string) ($context['point_key'] ?? '');
    $slotKey = (string) ($context['slot_key'] ?? '');
    $subjectId = (string) ($context['subject_id'] ?? '');

    $stmt = $pdo->prepare(
        "SELECT b.id, b.title, b.body_text, b.link_url, b.image_url
         FROM toy_banners b
         INNER JOIN toy_banner_targets t ON t.banner_id = b.id
         WHERE b.status = 'enabled'
           AND (b.starts_at IS NULL OR b.starts_at <= :now_start)
           AND (b.ends_at IS NULL OR b.ends_at >= :now_end)
           AND t.module_key = :module_key
           AND t.point_key = :point_key
           AND t.slot_key = :slot_key
           AND (t.match_type = 'all' OR (t.match_type = 'exact' AND t.subject_id = :subject_id))
         ORDER BY b.sort_order ASC, b.id DESC
         LIMIT 5"
    );
    $now = toy_now();
    $stmt->execute([
        'now_start' => $now,
        'now_end' => $now,
        'module_key' => $moduleKey,
        'point_key' => $pointKey,
        'slot_key' => $slotKey,
        'subject_id' => $subjectId,
    ]);

    $html = '';
    foreach ($stmt->fetchAll() as $banner) {
        $content = '';
        if ((string) $banner['image_url'] !== '') {
            $content .= '<img src="' . toy_e((string) $banner['image_url']) . '" alt="' . toy_e((string) $banner['title']) . '">';
        }
        $content .= '<strong>' . toy_e((string) $banner['title']) . '</strong>';
        if ((string) ($banner['body_text'] ?? '') !== '') {
            $content .= '<span>' . nl2br(toy_e((string) $banner['body_text'])) . '</span>';
        }

        if ((string) $banner['link_url'] !== '') {
            $content = '<a href="' . toy_e((string) $banner['link_url']) . '">' . $content . '</a>';
        }

        $html .= '<aside class="toy-banner" data-banner-id="' . toy_e((string) $banner['id']) . '">' . $content . '</aside>';
    }

    return $html;
}
