<?php
return [
    'default' => 'production',
    'connections' => [
        'production' => [
            'host'      => env('SSH_PRODUCTION_HOST', '127.0.0.1'),
            'username'  => env('SSH_PRODUCTION_USERNAME', 'root'),
            'password'  => env('SSH_PRODUCTION_PASSWORD', ''),
            'key'       => '',
            'keytext'   => '',
            'keyphrase' => '',
            'agent'     => '',
            'timeout'   => 10,
        ],
    ],
    'groups' => [
        'web' => [
            'production',
        ],
    ],
];
