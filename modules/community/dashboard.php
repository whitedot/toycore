<?php

return [
    [
        'key' => 'community',
        'title' => '커뮤니티',
        'order' => 50,
        'rows' => [
            [
                'label' => '게시글',
                'value_sql' => "SELECT COUNT(*) AS value FROM sr_community_posts WHERE status = 'published'",
                'detail_sql' => "SELECT CONCAT('댓글 ', COUNT(*)) AS detail FROM sr_community_comments WHERE status = 'published'",
            ],
            [
                'label' => '신고',
                'value_sql' => "SELECT COUNT(*) AS value FROM sr_community_reports WHERE status = 'open'",
                'detail_sql' => "SELECT CONCAT('게시판 ', COUNT(*)) AS detail FROM sr_community_boards WHERE status = 'enabled'",
            ],
        ],
    ],
];
