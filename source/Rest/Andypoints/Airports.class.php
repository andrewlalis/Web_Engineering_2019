<?php

namespace Rest\Andypoints;

use Rest\Endpoint;
use Rest\AndypointTypes\GetRequest;
use Rest\Response;

class Airports extends Endpoint implements GetRequest
{

    public function __construct()
    {
        parent::__construct('/airports');
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
        $statement = $this->getDb()->prepare('SELECT * FROM airports;');
        $result = $statement->execute();
        $airports = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $airports[] = $row;
        }

        return new Response(
            200,
            $airports
        );
    }
}