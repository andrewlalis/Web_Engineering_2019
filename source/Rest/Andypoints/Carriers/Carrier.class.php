<?php

namespace Rest\Andypoints\Carriers;


use Rest\AndypointTypes\GetRequest;
use Rest\Endpoint;
use Rest\Response;

class Carrier extends Endpoint implements GetRequest
{
    public function __construct()
    {
        parent::__construct('/carriers/{code}');
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

        $statement = $this->getDb()->prepare("SELECT * FROM carriers WHERE carrier_code = :carrier_code;");
        $statement->bindValue(':carrier_code', $code);
        $result = $statement->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        return new Response(
            200,
            $row,
            []
        );
    }
}