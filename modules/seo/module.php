<?php

return [
    'name' => 'SEO',
    'version' => '2026.04.002',
    'type' => 'module',
    'description' => 'SEO output helpers and sitemap endpoint.',
    'requires' => [
        'modules' => ['admin'],
    ],
    'settings' => [
        'title_suffix' => '',
        'default_description' => '',
        'default_og_image' => '',
        'sitemap_include_home' => true,
        'robots_disallow_paths' => "/admin\n/account\n/login\n/register\n/password/reset",
    ],
];
