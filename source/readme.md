# AndyFramework Server
This server makes use of the _AndyFramework_ PHP routing framework for REST API endpoints and controllers for visitable pages. Here you'll find a basic overview of how it works, how to define your own endpoints, and how to get everything up and running.

## Running the Server
PHP was so kind as to provide a built-in webserver for testing purposes, so that will be used during the development of this application. To start the webserver on `localhost` port `8000`, simply run the included `run-server.sh`. _Note: You may need to give execution permission to the file with `chmod +x run-server.sh`._

## Basic Architecture
Because of the scope of this project, it is not necessary to implement any sort of ORM to simplify database queries, since the database for this application is rather limited in size. Therefore, all endpoints must execute their own SQL query to fetch any needed information from the database.

### Andypoints
Endpoints, or _Andypoints_, as they are called here, are any REST endpoints in the application. Any Andypoint is first registered into the router by doing the following:

```php
$router = new Router();

// Register a list of endpoints.
$router->registerEndpoint(new Andypoints\Example());
$router->registerEndpoint(new Andypoints\Airports());
$router->registerEndpoint(new Andypoints\Airports\Airport());
```

For the sake of ease of use, in this application, registering endpoints is done in `index.php`.