<?php

return [
    'drivers' => [
        'smtp' => 'SMTP',
        'log' => 'Log (testing)',
    ],

    'providers' => [
        'smtp' => [
            'label' => 'Custom SMTP',
            'driver' => 'smtp',
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'delivery_tracking' => false,
        ],
        'gmail' => [
            'label' => 'Gmail / Google Workspace',
            'driver' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'delivery_tracking' => false,
        ],
        'outlook' => [
            'label' => 'Microsoft 365 / Outlook',
            'driver' => 'smtp',
            'host' => 'smtp.office365.com',
            'port' => 587,
            'encryption' => 'tls',
            'delivery_tracking' => false,
        ],
        'sendgrid' => [
            'label' => 'SendGrid',
            'driver' => 'smtp',
            'host' => 'smtp.sendgrid.net',
            'port' => 587,
            'encryption' => 'tls',
            'delivery_tracking' => true,
        ],
        'mailgun' => [
            'label' => 'Mailgun',
            'driver' => 'smtp',
            'host' => 'smtp.mailgun.org',
            'port' => 587,
            'encryption' => 'tls',
            'delivery_tracking' => true,
        ],
        'log' => [
            'label' => 'Log (testing)',
            'driver' => 'log',
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'delivery_tracking' => false,
        ],
    ],

    'encryptions' => [
        'tls' => 'TLS',
        'ssl' => 'SSL',
        'none' => 'None',
    ],

    'defaults' => [
        'enabled' => false,
        'provider' => 'smtp',
        'driver' => 'smtp',
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'from_address' => '',
        'from_name' => '',
        'reply_to' => '',
        'default_cc' => '',
        'default_bcc' => '',
        'signature' => '',
    ],
];
