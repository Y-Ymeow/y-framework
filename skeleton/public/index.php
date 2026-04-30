<?php

declare(strict_types=1);

use Framework\Foundation\Application;
use Framework\Foundation\Kernel;
use Framework\Http\Request;
use Framework\Error\ErrorHandler;

require __DIR__ . '/../vendor/autoload.php';

$basePath = dirname(__DIR__);

if (file_exists($basePath . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable($basePath);
    $dotenv->safeLoad();
}

$app = new Application($basePath);

$debug = config('app.debug', true);
ErrorHandler::register($debug);

$kernel = new Kernel($app);
$kernel->bootstrap();

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();