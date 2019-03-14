# AndyFramework Server
This server makes use of the _AndyFramework_ PHP routing framework for REST API endpoints and controllers for visitable pages. Here you'll find a basic overview of how it works, how to define your own endpoints, and how to get everything up and running.

## Running the Server
PHP was so kind as to provide a built-in webserver for testing purposes, so that will be used during the development of this application. To start the webserver on `localhost` port `8000`, simply run the included `run-server.sh`. _Note: You may need to give execution permission to the file with `chmod +x run-server.sh`._

## Basic Architecture
Because of the scope of this project, it is not necessary to implement any sort of ORM to simplify database queries, since the database for this application is rather limited in size. Therefore, all endpoints must execute their own SQL query to fetch any needed information from the database.

### Andypoints
