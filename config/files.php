<?php

return [

    'disk' => env('FILES_DISK', 'local'),

    'directory' => env('FILES_DIRECTORY', 'uploads'),

    'max_size_kb' => (int) env('FILES_MAX_SIZE_KB', 10240),

    'allowed_extensions' => ['pdf', 'docx'],

    'ttl_minutes' => (int) env('FILES_TTL_MINUTES', 1440),

    'notification_email' => env('FILES_NOTIFICATION_EMAIL', 'admin@example.com'),

    'notification_connection' => env('FILES_NOTIFICATION_CONNECTION', 'rabbitmq'),

    'notification_queue' => env('FILES_NOTIFICATION_QUEUE', 'file-notifications'),

];
