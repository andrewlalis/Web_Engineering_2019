<?php

// The name of the database file.
define('DB_NAME', 'fly_ATG.sqlite');
// The name of the server.
define('HOST_NAME', 'http://localhost:8000');
// The sub-domain for API calls.
define('API_NAME', '/api');

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



// If this is an API call, then use the rest router.
if (substr($_SERVER['REQUEST_URI'], 0, strlen(API_NAME)) === API_NAME) {
    // Delegate functionality to the router.
    $rest_router = new Router();

    // Register a list of endpoints.
    $rest_router->registerEndpoint(new Andypoints\Example());
    $rest_router->registerEndpoint(new Andypoints\Airports());
    $rest_router->registerEndpoint(new Andypoints\Airports\Airport());
    $rest_router->registerEndpoint(new Andypoints\Carriers());
    $rest_router->registerEndpoint(new Andypoints\Carriers\Carrier());
    $rest_router->registerEndpoint(new Andypoints\Statistics(Andypoints\Statistics::LOCATION));
    $rest_router->registerEndpoint(new Andypoints\Statistics\Flights());
    $rest_router->registerEndpoint(new Andypoints\Statistics\Delays());
    $rest_router->registerEndpoint(new Andypoints\Statistics\MinutesDelayed());
    $rest_router->registerEndpoint(new Andypoints\AggregateCarrierStatistics());

    // Process the current request.
    $rest_router->respond(substr($_SERVER['REQUEST_URI'], strlen(API_NAME)), $_SERVER['REQUEST_METHOD'], getallheaders());
} else {
    // Process a call which will return some HTML.

}
