<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/community/helpers.php';

$account = toy_member_current_account($pdo);
$boards = [];
foreach (toy_community_enabled_boards($pdo) as $board) {
    if (toy_community_account_can_read_board($pdo, $board, is_array($account) ? $account : null)) {
        $boards[] = $board;
    }
}
$settings = toy_module_settings($pdo, 'community');
$themeKey = toy_community_theme_key($settings);
$themeView = toy_community_theme_view($themeKey, 'home');

include $themeView;
