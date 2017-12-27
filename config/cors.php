<?php

return [
    'supportsCredentials' => false,
    'allowedOrigins' => ["http://app.forestadmin.com", "https://app.forestadmin.com"],
    'allowedHeaders' => ['*'],
    'allowedMethods' => ['POST', 'PUT', 'GET', 'DELETE'],
    'exposedHeaders' => [],
    'maxAge' => 86400, // NOTICE: 1 day
    'hosts' => [],
];
