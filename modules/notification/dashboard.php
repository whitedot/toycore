<?php

return [
    [
        'key' => 'notification',
        'title' => '알림',
        'order' => 40,
        'rows' => [
            [
                'label' => '전체 알림',
                'value_sql' => 'SELECT COUNT(*) AS value FROM sr_notifications',
                'detail_sql' => "SELECT CONCAT('발송 대기 ', COUNT(*)) AS detail FROM sr_notification_deliveries WHERE status = 'queued'",
            ],
        ],
    ],
];
