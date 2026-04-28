<?php

declare(strict_types=1);

use Framework\Foundation\Application;
use Framework\Foundation\Kernel;
use Framework\Http\Request;
use Framework\Error\ErrorHandler;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = new Application(dirname(__DIR__));

$debug = config('app.debug', true);
ErrorHandler::register($debug);

$kernel = new Kernel($app);
$kernel->bootstrap();

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
