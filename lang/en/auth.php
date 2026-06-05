<?php

return [
    'fields' => [
        'name' => 'name',
        'email' => 'email address',
        'password' => 'password',
    ],

    'labels' => [
        'name' => 'Name',
        'email' => 'Email',
        'email_address' => 'Email address',
        'password' => 'Password',
        'confirm_password' => 'Confirm password',
        'current_password' => 'Current password',
        'new_password' => 'New password',
        'otp_code' => 'OTP code',
    ],

    'placeholders' => [
        'email' => 'email@example.com',
        'full_name' => 'Full name',
        'password' => 'Password',
        'confirm_password' => 'Confirm password',
        'passkey_name' => 'e.g., MacBook Pro, iPhone',
    ],

    'actions' => [
        'back' => 'Back',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'continue' => 'Continue',
        'logout' => 'Log out',
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

    'register' => [
        'title' => 'Register',
        'heading' => 'Create an account',
        'description' => 'Enter your details below to create your account.',
        'submit' => 'Create account',
        'login_prompt' => 'Already have an account?',
        'login' => 'Log in',
    ],

    'forgot_password' => [
        'title' => 'Forgot password',
        'heading' => 'Forgot password',
        'description' => 'Enter your email to receive a password reset link.',
        'submit' => 'Email password reset link',
        'return_prompt' => 'Or, return to',
        'login' => 'log in',
    ],

    'reset_password' => [
        'title' => 'Reset password',
        'heading' => 'Reset password',
        'description' => 'Please enter your new password below.',
        'submit' => 'Reset password',
    ],

    'confirm_password' => [
        'title' => 'Confirm password',
        'heading' => 'Confirm password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'submit' => 'Confirm',
    ],

    'verify_email' => [
        'title' => 'Email verification',
        'instructions' => 'Please verify your email address by clicking on the link we just emailed to you.',
        'sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend verification email',
    ],

    'two_factor' => [
        'title' => 'Two-factor authentication',
        'authentication_code' => [
            'title' => 'Authentication code',
            'description' => 'Enter the authentication code provided by your authenticator application.',
        ],
        'recovery_code' => [
            'title' => 'Recovery code',
            'description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
        ],
        'switch_prompt' => 'or you can',
        'use_recovery_code' => 'log in using a recovery code',
        'use_authentication_code' => 'log in using an authentication code',
    ],

    'passkeys' => [
        'sign_in' => 'Sign in with a passkey',
        'authenticating' => 'Authenticating...',
        'email_separator' => 'Or continue with email',
        'confirm' => 'Confirm with passkey',
        'confirming' => 'Confirming...',
        'password_separator' => 'Or confirm with password',
        'unsupported' => 'Passkeys are not supported in this browser.',
        'add' => 'Add passkey',
        'name' => 'Passkey name',
        'name_help' => 'Give this passkey a name to help you identify it later.',
        'register' => 'Register passkey',
        'registering' => 'Registering...',
    ],

    'demo' => [
        'heading' => 'Demo users',
        'description' => 'Available only in local, testing, and demo environments.',
        'email' => 'Email',
        'password' => 'Password',
        'quick_login' => 'Log in as :name',
        'users' => [
            'test' => [
                'role' => 'Primary demo admin workspace',
                'description' => 'A seeded personal workspace with realistic projects, tags, filters, task states, and maintenance access.',
            ],
            'second' => [
                'role' => 'Isolation demo workspace',
                'description' => 'A separate seeded workspace used to verify private data boundaries.',
            ],
        ],
    ],
];
