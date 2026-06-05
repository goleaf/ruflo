<?php

return [
    'fields' => [
        'name' => 'name',
        'email' => 'email address',
        'password' => 'password',
    ],

    'validation' => [
        'email_unique' => 'This email address is already registered.',
    ],

    'login' => [
        'title' => 'Log in',
        'heading' => 'Log in to your account',
        'description' => 'Enter your email and password below to log in.',
        'email' => 'Email address',
        'email_placeholder' => 'email@example.com',
        'password' => 'Password',
        'remember' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'submit' => 'Log in',
        'signup_prompt' => 'Don\'t have an account?',
        'signup' => 'Sign up',
    ],

    'demo' => [
        'heading' => 'Demo users',
        'description' => 'Available only in local, testing, and demo environments.',
        'email' => 'Email',
        'password' => 'Password',
        'quick_login' => 'Log in as :name',
        'users' => [
            'test' => [
                'role' => 'Primary demo workspace',
                'description' => 'A seeded personal workspace with realistic projects, tags, filters, and task states.',
            ],
            'second' => [
                'role' => 'Isolation demo workspace',
                'description' => 'A separate seeded workspace used to verify private data boundaries.',
            ],
        ],
    ],
];
