<?php

namespace Rest\Andypoints;

use Rest\Endpoint;
use Rest\AndypointTypes\GetRequest;
use Rest\Response;

/**
 * This is an example endpoint, which implements GET, meaning that it must do something when a GET request is sent to
 * the uri defined in its constructor.
 *
 * In the constructor, the parent constructor is called with a uri string, which includes the `{value}` path parameter.
 * This means that when a GET request is sent to this endpoint, a value must be placed in the uri. For example, this
 * endpoint is reachable by going to 'http://localhost:8000/example/123' But, going to 'http://localhost:8000/example'
 * will return a 404.
 */
class Example extends Endpoint implements GetRequest
{
    /**
     * Example constructor, which calls the parent with the name of the uri at which this resource can be reached. It
     * is also possible to ask for a string in this constructor, so that the uri is provided when this endpoint is
     * instantiated.
     */
    public function __construct()
    {
        parent::__construct('/example/{value}');
    }

    /**
     * Responds to a GET request to this resource.
     *
     * @param array $path_args Any path arguments provided, with the same name as declared by the URI used to construct
     * the endpoint which implements this interface.
     * @param array $args A string-indexed list of arguments provided by the client.
     *
     * @return Response A response to this request. This contains both a response code, and an array of data to send
     * back to the client.
     */
    public function get(array $path_args, array $args): Response
    {
        return new Response(200, [
            'message' => 'Thank you for sending a value to the example endpoint!',
            'value' => $path_args['value'],
            'query_parameters' => $args,
            'uri' => $this->getUri()
        ], []);
    }
}