<?php

declare(strict_types=1);

require_once SR_ROOT . '/modules/member/helpers.php';
require_once SR_ROOT . '/modules/admin/helpers.php';

$account = sr_member_require_login($pdo);
sr_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$commonCssPath = SR_ROOT . '/assets/common.css';
$commonCss = is_file($commonCssPath) ? (string) file_get_contents($commonCssPath) : '';

function sr_admin_design_tokens_add_value(array &$target, string $key, string $value): void
{
    $value = trim($value);
    if ($key === '' || $value === '') {
        return;
    }

    if (!isset($target[$key])) {
        $target[$key] = [];
    }

    if (!in_array($value, $target[$key], true)) {
        $target[$key][] = $value;
    }
}

function sr_admin_design_tokens_rule_selector(string $css, int $offset): string
{
    $beforeDeclaration = substr($css, 0, $offset);
    $bracePosition = strrpos($beforeDeclaration, '{');
    if ($bracePosition === false) {
        return '';
    }

    $beforeBrace = substr($css, 0, $bracePosition);
    $lastClose = strrpos($beforeBrace, '}');
    $lastOpen = strrpos($beforeBrace, '{');
    $selectorStart = max($lastClose === false ? -1 : $lastClose, $lastOpen === false ? -1 : $lastOpen) + 1;

    return trim(substr($css, $selectorStart, $bracePosition - $selectorStart));
}

function sr_admin_design_tokens_token_category(string $name): string
{
    if (str_starts_with($name, '--color-')) {
        return '색상';
    }

    if (str_starts_with($name, '--font-') || str_starts_with($name, '--text-') || str_starts_with($name, '--leading-')) {
        return '타이포그래피';
    }

    if (str_starts_with($name, '--spacing') || str_starts_with($name, '--container-')) {
        return '간격과 레이아웃';
    }

    if (str_starts_with($name, '--radius')) {
        return '모서리';
    }

    if (str_starts_with($name, '--shadow') || str_starts_with($name, '--inset-shadow')) {
        return '그림자';
    }

    if (str_contains($name, 'transition') || str_contains($name, 'duration') || str_starts_with($name, '--ease-')) {
        return '모션';
    }

    if (str_starts_with($name, '--tw-')) {
        return '내부 속성';
    }

    return '기타';
}

function sr_admin_design_tokens_class_category(string $className): string
{
    if ($className === 'btn' || str_starts_with($className, 'btn-')) {
        return '버튼';
    }

    if ($className === 'badge' || str_starts_with($className, 'badge-')) {
        return '배지';
    }

    if ($className === 'hint-text' || $className === 'validation-icon') {
        return '유효성 및 피드백';
    }

    if (str_starts_with($className, 'form-') || str_starts_with($className, 'input-') || str_starts_with($className, 'password-') || str_starts_with($className, 'ui-form-') || str_starts_with($className, 'ui-floating-') || str_starts_with($className, 'af-')) {
        return '폼 컨트롤';
    }

    if ($className === 'card' || str_starts_with($className, 'card-')) {
        return '카드';
    }

    if ($className === 'table' || str_starts_with($className, 'table-') || $className === 'pagination' || str_starts_with($className, 'page-')) {
        return '테이블과 페이지네이션';
    }

    if ($className === 'nav-tabs' || $className === 'nav-link' || str_starts_with($className, 'nav-link-') || str_starts_with($className, 'tab-')) {
        return '탭과 내비게이션';
    }

    if ($className === 'dropdown' || str_starts_with($className, 'dropdown-') || str_starts_with($className, 'hs-dropdown')) {
        return '드롭다운';
    }

    if (str_starts_with($className, 'modal-') || str_starts_with($className, 'hs-overlay') || $className === 'close-icon') {
        return '모달과 오버레이';
    }

    if ($className === 'container' || $className === 'container-fluid' || $className === 'app-header' || $className === 'page-content' || $className === 'footer') {
        return '간격과 레이아웃';
    }

    if ($className === 'hidden' || $className === 'relative' || $className === 'sr-only' || $className === 'caption-sr-only' || $className === 'peer' || $className === 'text-dark' || $className === 'iconify-icon' || str_starts_with($className, 'animate-') || str_starts_with($className, 'progress-')) {
        return '유틸리티';
    }

    return '기타';
}

function sr_admin_design_tokens_extract_tokens(string $css): array
{
    preg_match_all('/(--[A-Za-z0-9_-]+)\s*:\s*([^;{}]+);/', $css, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $tokens = [];
    foreach ($matches as $match) {
        $name = trim((string) $match[1][0]);
        $value = trim((string) $match[2][0]);
        $offset = (int) $match[0][1];
        if ($name === '' || $value === '') {
            continue;
        }

        if (!isset($tokens[$name])) {
            $tokens[$name] = [
                'name' => $name,
                'category' => sr_admin_design_tokens_token_category($name),
                'values' => [],
                'root_values' => [],
                'dark_values' => [],
                'other_values' => [],
                'property_values' => [],
            ];
        }

        sr_admin_design_tokens_add_value($tokens[$name], 'values', $value);
        $selector = sr_admin_design_tokens_rule_selector($css, $offset);
        if (str_contains($selector, ':root')) {
            sr_admin_design_tokens_add_value($tokens[$name], 'root_values', $value);
        } elseif (str_contains($selector, '[data-theme=dark]')) {
            sr_admin_design_tokens_add_value($tokens[$name], 'dark_values', $value);
        } else {
            sr_admin_design_tokens_add_value($tokens[$name], 'other_values', $value);
        }
    }

    preg_match_all('/@property\s+(--[A-Za-z0-9_-]+)\s*\{([^{}]*)\}/', $css, $propertyMatches, PREG_SET_ORDER);
    foreach ($propertyMatches as $propertyMatch) {
        $name = trim((string) $propertyMatch[1]);
        if ($name === '') {
            continue;
        }

        if (!isset($tokens[$name])) {
            $tokens[$name] = [
                'name' => $name,
                'category' => sr_admin_design_tokens_token_category($name),
                'values' => [],
                'root_values' => [],
                'dark_values' => [],
                'other_values' => [],
                'property_values' => [],
            ];
        }

        $body = trim((string) $propertyMatch[2]);
        if ($body !== '') {
            sr_admin_design_tokens_add_value($tokens[$name], 'property_values', $body);
        }
    }

    ksort($tokens, SORT_NATURAL);

    return array_values($tokens);
}

function sr_admin_design_tokens_extract_classes(string $css): array
{
    preg_match_all('/(?<![A-Za-z0-9_-])\.([A-Za-z_][A-Za-z0-9_-]*)/', $css, $matches);
    $classes = array_values(array_unique(array_map('strval', $matches[1] ?? [])));
    sort($classes, SORT_NATURAL);

    $records = [];
    foreach ($classes as $className) {
        $records[] = [
            'name' => $className,
            'category' => sr_admin_design_tokens_class_category($className),
        ];
    }

    return $records;
}

function sr_admin_design_tokens_group_records(array $records): array
{
    $groups = [];
    foreach ($records as $record) {
        $groups[(string) $record['category']][] = $record;
    }

    return $groups;
}

function sr_admin_design_tokens_filter_classes(array $records, string $category): array
{
    return array_values(array_filter($records, static function (array $record) use ($category): bool {
        return ($record['category'] ?? '') === $category;
    }));
}

$designTokenRecords = sr_admin_design_tokens_extract_tokens($commonCss);
$designClassRecords = sr_admin_design_tokens_extract_classes($commonCss);
$designTokenGroups = sr_admin_design_tokens_group_records($designTokenRecords);
$designClassGroups = sr_admin_design_tokens_group_records($designClassRecords);

$designTokenCategoryOrder = [
    '색상',
    '타이포그래피',
    '간격과 레이아웃',
    '모서리',
    '그림자',
    '모션',
    '내부 속성',
    '기타',
];
$designClassCategoryOrder = [
    '버튼',
    '배지',
    '폼 컨트롤',
    '유효성 및 피드백',
    '카드',
    '테이블과 페이지네이션',
    '탭과 내비게이션',
    '드롭다운',
    '모달과 오버레이',
    '간격과 레이아웃',
    '유틸리티',
    '기타',
];

$designTokenSummary = [
    'token_count' => count($designTokenRecords),
    'class_count' => count($designClassRecords),
    'css_path' => str_replace(SR_ROOT . '/', '', $commonCssPath),
];

$designButtonClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '버튼');
$designBadgeClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '배지');
$designFormClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '폼 컨트롤');
$designFeedbackClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '유효성 및 피드백');
$designCardClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '카드');
$designTableClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '테이블과 페이지네이션');
$designTabClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '탭과 내비게이션');
$designDropdownClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '드롭다운');
$designModalClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '모달과 오버레이');
$designUtilityClasses = sr_admin_design_tokens_filter_classes($designClassRecords, '유틸리티');

include SR_ROOT . '/modules/admin/views/design-tokens.php';
