<?php

return [
    [
        'point_key' => 'member.login',
        'label' => '로그인',
        'surface' => 'public',
        'output' => true,
        'slots' => [
            [
                'slot_key' => 'before_form',
                'label' => '폼 위',
                'kind' => 'content',
            ],
            [
                'slot_key' => 'after_form',
                'label' => '폼 아래',
                'kind' => 'content',
            ],
        ],
    ],
    [
        'point_key' => 'member.register',
        'label' => '회원가입',
        'surface' => 'public',
        'output' => true,
        'slots' => [
            [
                'slot_key' => 'before_form',
                'label' => '폼 위',
                'kind' => 'content',
            ],
            [
                'slot_key' => 'after_form',
                'label' => '폼 아래',
                'kind' => 'content',
            ],
        ],
    ],
];
