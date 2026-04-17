<?php

declare(strict_types=1);

use Framework\Core\Kernel;
use Framework\Http\Request;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$response = $kernel->handle(Request::capture());
$response->send();
