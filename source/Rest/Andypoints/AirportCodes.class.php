<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\Endpoint;
use Rest\Response;

/**
 * A convenience endpoint which returns a list of all available airport codes.
 */
class AirportCodes extends Endpoint implements GetRequest
{
    const LOCATION = '/airport_codes';

    public function __construct()
    {
        parent::__construct(static::LOCATION);
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
        $result = $this->fetchCollection("SELECT airport_code FROM airports;");
        return new Response(200, $result);
    }
}