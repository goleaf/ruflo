<?php

return [
    'login_panel' => [
        'enabled' => env('RUFLO_DEMO_LOGIN_PANEL', true),
        'environments' => ['local', 'testing', 'demo'],
        'password' => env('RUFLO_DEMO_PASSWORD', 'password'),
        'users' => [
            [
                'name' => 'Avery Chen',
                'email' => 'test@example.com',
                'role_key' => 'auth.demo.users.test.role',
                'description_key' => 'auth.demo.users.test.description',
            ],
            [
                'name' => 'Morgan Blake',
                'email' => 'second@example.com',
                'role_key' => 'auth.demo.users.second.role',
                'description_key' => 'auth.demo.users.second.description',
            ],
        ],
    ],
];
