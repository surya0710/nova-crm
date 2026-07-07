<?php

return [
    'email_attachments' => [
        'max_files' => 5,
        'max_size_kb' => 10240,
        'allowed_mimes' => [
            'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp',
            'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip',
        ],
    ],
];
