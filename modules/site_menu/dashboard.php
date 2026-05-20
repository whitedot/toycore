<?php

return [
    [
        'key' => 'site_menu',
        'title' => '사이트 메뉴',
        'order' => 10,
        'view' => 'views/dashboard-summary.php',
        'items' => [
            [
                'label' => '활성 메뉴',
                'value_sql' => "SELECT COUNT(*) AS value FROM sr_site_menus WHERE status = 'enabled'",
                'detail_sql' => "SELECT CONCAT('활성 항목 ', COUNT(*)) AS detail FROM sr_site_menu_items WHERE status = 'enabled'",
                'state' => 'success',
                'emphasis' => 'primary',
            ],
        ],
    ],
];
