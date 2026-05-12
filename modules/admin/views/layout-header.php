<?php

$adminSettings = isset($pdo) && $pdo instanceof PDO ? toy_admin_settings($pdo) : ['admin_skin_key' => 'basic'];
$adminSkinView = toy_admin_skin_view(toy_admin_skin_key($adminSettings), 'layout-header');
include $adminSkinView;
