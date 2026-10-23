<?php

return [
    'host' => env('RABBITMQ_HOST', 'host.docker.internal'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'atk'),
    'password' => env('RABBITMQ_PASSWORD', 'atk'),
    'vhost' => env('RABBITMQ_VHOST', '/'),
    'exchange' => 'atk.events',
];
