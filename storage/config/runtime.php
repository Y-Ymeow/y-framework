<?php return array (
  'config' => 
  array (
    'model' => 'Framework\\Module\\User\\User',
    'defaults' => 
    array (
      'role' => 'user',
      'avatar' => NULL,
    ),
    'password' => 
    array (
      'min_length' => 8,
      'hash_algorithm' => '2y',
      'hash_cost' => 12,
    ),
    'remember' => 
    array (
      'enabled' => true,
      'cookie_name' => 'remember_token',
      'cookie_lifetime' => 43200,
    ),
    'verification' => 
    array (
      'enabled' => true,
      'expire' => 60,
    ),
    'database' => 
    array (
      'enabled' => true,
      'table' => 'notifications',
      'prune_after_days' => 30,
    ),
    'channels' => 
    array (
      'database' => true,
      'sse' => true,
    ),
    'sse' => 
    array (
      'channel' => 'notifications',
    ),
  ),
);