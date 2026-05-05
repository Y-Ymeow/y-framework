<?php

return [
    'model' => \Framework\Module\User\User::class,

    'defaults' => [
        'role' => 'user',
        'avatar' => null,
    ],

    'password' => [
        'min_length' => 8,
        'hash_algorithm' => PASSWORD_BCRYPT,
        'hash_cost' => 12,
    ],

    'remember' => [
        'enabled' => true,
        'cookie_name' => 'remember_token',
        'cookie_lifetime' => 43200,
    ],

    'verification' => [
        'enabled' => true,
        'expire' => 60,
    ],
];
