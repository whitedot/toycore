<?php

require_once TOY_ROOT . '/modules/site_menu/helpers.php';

return static function (PDO $pdo, array $context): string {
    $moduleKey = (string) ($context['module_key'] ?? '');
    $pointKey = (string) ($context['point_key'] ?? '');
    $slotKey = (string) ($context['slot_key'] ?? '');

    if ($moduleKey === 'core' && $pointKey === 'site.home' && $slotKey === 'navigation') {
        return toy_site_menu_render($pdo, 'header');
    }

    return '';
};
