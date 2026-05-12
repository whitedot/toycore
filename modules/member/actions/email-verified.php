<?php

declare(strict_types=1);

$memberSettings = toy_member_settings($pdo);
$memberSkinView = toy_member_skin_view(toy_member_skin_key($memberSettings), 'email-verified');
include $memberSkinView;
