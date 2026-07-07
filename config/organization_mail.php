<?php

return [
    'drivers' => [
        'smtp' => 'SMTP',
        'log' => 'Log (testing)',
    ],

    'encryptions' => [
        'tls' => 'TLS',
        'ssl' => 'SSL',
        'none' => 'None',
    ],

    'defaults' => [
        'enabled' => false,
        'driver' => 'smtp',
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'from_address' => '',
        'from_name' => '',
    ],
];
