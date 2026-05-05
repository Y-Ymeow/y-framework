<?php

declare(strict_types=1);

use Framework\Foundation\Application;
use Framework\Foundation\Kernel;
use Framework\Http\Request\Request;
use Framework\Error\ErrorHandler;

require dirname(__DIR__) . '/vendor/autoload.php';

Application::setDebug(true);
$app = new Application(dirname(__DIR__));

$debug = $app::isDebug();
ErrorHandler::register($debug);

$kernel = new Kernel($app);
$kernel->bootstrap();

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
