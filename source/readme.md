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

For the sake of ease of use, in this application, registering endpoints is done in `index.php`. All Andypoints should implement one of request types found in `Rest/AndypointTypes`, and should therefore implement the respective method for that request type. To see an example of how to write an endpoint, take a look at `Rest/Andypoints/Example.class.php`.

### Router
Although the average programmer does not need to concern himself with how the router works, and needs only create endpoints, the workings of the router are explained here.

#### Endpoint Registry
The router works by keeping a registry of all endpoints available on the server. New endpoints are added by calling `registerEndpoint(Endpoint $endpoint)`. Each time a request is received, the URI, request type, and headers are passed to the router. Then, the router will compare the requested URI with the URI of each endpoint, until it finds one which matches the same format. (This means matching with any path parameters an endpoint has defined.)

Using regular expressions, a string-indexed array of URI parameters is generated from the request URI so that each parameter specified in the endpoint's URI has a value. For example, the `Example` endpoint supplies the URI `/example/{value}`. Thus, the URI parameters array is simply `['value' => '...']`.

Once a matching endpoint is found, a response is elicited by calling `$endpoint->getResponse($request_type, $uri_parameters)`.

#### Response Processing
Once a response from an endpoint is gotten, the router is responsible for formatting it for transmission back to the client. In the case of this application, that means checking to see whether or not the client has explicitly stated that they accept CSV format. This is done by checking the `Accept` header.

A number of links need to also be provided which allow navigation through the API, so this is provided by the router as well.

Finally, if an error occurs during the finding of an endpoint, retrieval of a response, or other error, the router is responsible for outputting a sensible error message.

#### Paginated Endpoints
Many endpoints will return a collection of resources. It would be tedious to require each of these endpoints to manually define SQL for each possible argument that could be supplied, which would change the total number of resources returned.

To simplify this, `PaginatedEndpoint` objects do most of the hard work of generating links to different pages of the endpoint's resources, and creating the proper SQL. Each endpoint which extends `PaginatedEndpoint` must therefore implement a few methods to help the parent determine what to use in its query:

* `getTableDeclaration()` defines the table that will be selected from. This can be a simple table name, or as many joins as you like.
* `getResourceIdentifierName()` defines the name of the unique identifier for each resource in the collection.
* `getConditionBuilder()` can be extended to determine how the endpoint handles extra query parameters. In this method, you must return a `ConditionBuilder` which has had several `Conjunct` objects added to it, each of which defines one condition on which to filter the selection of resources.
* `getResponseColumnNames()` can be extended to specify only specific columns to be included in the response. By default, all columns (`*`) are returned.
* `getAdditionalResourceLinks()` allows for the definition of links to resources besides an individual resource itself. For example, a resource may be related to other resources, in which case it should supply links to these resources.