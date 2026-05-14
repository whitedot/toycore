<?php

return [
    [
        'key' => 'banner',
        'title' => '배너',
        'order' => 20,
        'rows' => [
            [
                'label' => '활성 배너',
                'value_sql' => "SELECT COUNT(*) AS value FROM sr_banners WHERE status = 'enabled'",
                'detail_sql' => "SELECT CONCAT('임시저장 ', COUNT(*)) AS detail FROM sr_banners WHERE status = 'draft'",
            ],
        ],
    ],
];
