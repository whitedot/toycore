<?php

return [
    [
        'point_key' => 'member.login',
        'label' => '로그인',
        'surface' => 'public',
        'output' => true,
        'slots' => [
            [
                'slot_key' => 'overlay',
                'label' => '화면',
                'kind' => 'overlay',
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
                'slot_key' => 'overlay',
                'label' => '화면',
                'kind' => 'overlay',
            ],
        ],
    ],
];
