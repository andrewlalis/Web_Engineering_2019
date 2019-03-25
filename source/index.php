<?php

// The name of the database file.
define('DB_NAME', 'fly_ATG.sqlite');
// The name of the server.
define('HOST_NAME', 'http://localhost:8000');

// Automatically load all classes as they are needed.
spl_autoload_register(function (string $class_name) {
    $class_name = str_replace('\\', '/', $class_name);
    include(__DIR__ . '/' . $class_name . '.class.php');
});

use Rest\Andypoints;
use Rest\Router;

// Initialize the database if it doesn't exist yet.
if (!file_exists(DB_NAME)) {
    require_once 'SQLite_initializer.php';
}

// Delegate functionality to the router.
$router = new Router();

// Register a list of endpoints.
$router->registerEndpoint(new Andypoints\Example());

$router->registerEndpoint(new Andypoints\Airports());
$router->registerEndpoint(new Andypoints\Airports\Airport());

$router->registerEndpoint(new Andypoints\Carriers());
$router->registerEndpoint(new Andypoints\Carriers\Carrier());

$router->registerEndpoint(new Andypoints\Statistics(Andypoints\Statistics::LOCATION));
$router->registerEndpoint(new Andypoints\Statistics\Flights());
$router->registerEndpoint(new Andypoints\Statistics\Delays());
$router->registerEndpoint(new Andypoints\Statistics\MinutesDelayed());

$router->registerEndpoint(new Andypoints\AggregateCarrierStatistics());

// Process the current request.
$router->respond($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], getallheaders());
