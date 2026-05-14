<?php

return [
    [
        'key' => 'popup_layer',
        'title' => '팝업레이어',
        'order' => 30,
        'rows' => [
            [
                'label' => '활성 팝업',
                'value_sql' => "SELECT COUNT(*) AS value FROM sr_popup_layers WHERE status = 'enabled'",
                'detail_sql' => "SELECT CONCAT('임시저장 ', COUNT(*)) AS detail FROM sr_popup_layers WHERE status = 'draft'",
            ],
        ],
    ],
];
