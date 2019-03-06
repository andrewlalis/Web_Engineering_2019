<?php

namespace Rest\Endpoints;

use Rest\Endpoint;
use Rest\EndpointTypes\GetRequest;
use Rest\Response;

class Airports extends Endpoint implements GetRequest
{

    public function __construct()
    {
        parent::__construct('/airports');
    }

    public function get(array $args): Response
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