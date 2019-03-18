<?php

namespace Rest\Andypoints\Airports;

use Rest\Endpoint;
use Rest\AndypointTypes\GetRequest;
use Rest\Response;

class Airport extends Endpoint implements GetRequest
{

    public function __construct()
    {
        parent::__construct('/airports/{code}');
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
        $code = filter_var($path_args['code'], FILTER_SANITIZE_STRING);

        $statement = $this->getDb()->prepare("SELECT * FROM airports WHERE airport_code = :airport_code;");
        $statement->bindValue(':airport_code', $code);

        $result = $statement->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row === false) {
            $row = [];
        }

        //$row = $this->getDb()->query("SELECT * FROM airports WHERE airport_code = 'ORD';")->fetchArray(SQLITE3_ASSOC);

        return new Response(
            200,
            $row,
            []
        );
    }
}