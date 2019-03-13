<?php

define('DB_NAME', 'fly_ATG.sqlite');

// Automatically load all classes as they are needed.
spl_autoload_register(function (string $class_name) {
    $class_name = str_replace('\\', '/', $class_name);
    include(__DIR__ . '/' . $class_name . '.class.php');
});

use Rest\Endpoints;
use Rest\Router;

// Initialize the database if it doesn't exist yet.
if (!file_exists(DB_NAME)) {
    require_once 'SQLite_initializer.php';
}

// Delegate functionality to the router.
$router = new Router();

// Register a list of endpoints.
$router->registerEndpoint(new Endpoints\Example());
$router->registerEndpoint(new Endpoints\Airports());
$router->registerEndpoint(new Endpoints\Airports\Airport());

// Process the current request.
$router->respond($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], getallheaders());
