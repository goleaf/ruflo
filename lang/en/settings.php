<?php

return [
    'title' => 'Settings',
    'description' => 'Manage your profile and account settings',

    'navigation' => [
        'aria' => 'Settings',
        'profile' => 'Profile',
        'security' => 'Security',
        'appearance' => 'Appearance',
    ],

    'actions' => [
        'back' => 'Back',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'continue' => 'Continue',
        'save' => 'Save',
    ],

    'profile' => [
        'title' => 'Profile settings',
        'heading' => 'Profile',
        'subheading' => 'Update your name and email address',
        'unverified' => 'Your email address is unverified.',
        'resend_verification' => 'Click here to re-send the verification email.',
        'updated' => 'Profile updated.',
        'verification_sent' => 'A new verification link has been sent to your email address.',
    ],

    'delete_account' => [
        'heading' => 'Delete account',
        'subheading' => 'Delete your account and all of its resources',
        'button' => 'Delete account',
        'confirm_heading' => 'Are you sure you want to delete your account?',
        'confirm_body' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
    ],

    'appearance' => [
        'title' => 'Appearance settings',
        'heading' => 'Appearance',
        'subheading' => 'Update the appearance settings for your account',
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],

    'security' => [
        'title' => 'Security settings',
        'password_heading' => 'Update password',
        'password_subheading' => 'Ensure your account is using a long, random password to stay secure',
        'password_updated' => 'Password updated.',

        'two_factor' => [
            'heading' => 'Two-factor authentication',
            'subheading' => 'Manage your two-factor authentication settings',
            'enabled_body' => 'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
            'disabled_body' => 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
            'enable' => 'Enable 2FA',
            'disable' => 'Disable 2FA',
            'enabled_title' => 'Two-factor authentication enabled',
            'enabled_description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
            'verify_title' => 'Verify authentication code',
            'verify_description' => 'Enter the 6-digit code from your authenticator app.',
            'setup_title' => 'Enable two-factor authentication',
            'setup_description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.',
            'manual_entry' => 'or, enter the code manually',
            'setup_data_failed' => 'Failed to fetch setup data.',
            'copy_failed' => 'Could not copy to clipboard',
        ],

        'recovery_codes' => [
            'heading' => '2FA recovery codes',
            'description' => 'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
            'view' => 'View recovery codes',
            'hide' => 'Hide recovery codes',
            'regenerate' => 'Regenerate codes',
            'aria' => 'Recovery codes',
            'note' => 'Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.',
            'load_failed' => 'Failed to load recovery codes',
        ],

        'passkeys' => [
            'heading' => 'Passkeys',
            'subheading' => 'Manage your passkeys for passwordless sign-in',
            'added' => 'Added :time',
            'last_used' => 'Last used :time',
            'empty_heading' => 'No passkeys yet',
            'empty_description' => 'Add a passkey to sign in without a password',
            'remove_heading' => 'Remove passkey',
            'remove_confirmation' => 'Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.',
            'remove' => 'Remove passkey',
        ],
    ],
];
