<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

// we need to define the constants, not include the whole setup
//require_once "../config/providence-setup.php"; // @todo: what should this be called??

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
//$response = $kernel->handle($request);
$response = $kernel->handle($request);

//include "providence/index.php";
//$response->send();
//$kernel->terminate($request, $response);

if ($response->getStatusCode() !== 404) {
    $response->send();
    $kernel->terminate($request, $response);
    exit();
}

