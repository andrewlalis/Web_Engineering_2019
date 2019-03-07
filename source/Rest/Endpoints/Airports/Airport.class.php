<?php

namespace Rest\Endpoints\Airports;

use Rest\Endpoint;
use Rest\EndpointTypes\GetRequest;
use Rest\Response;

class Airport extends Endpoint implements GetRequest
{

    public function __construct()
    {
        parent::__construct('/airports/{code}');
    }

    public function get(array $args): Response
    {
        $code = filter_var($args['code'], FILTER_SANITIZE_STRING);

        $statement = $this->getDb()->prepare("SELECT * FROM airports WHERE airport_code = :airport_code;");
        $statement->bindValue(':airport_code', $code);

        $result = $statement->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        //$row = $this->getDb()->query("SELECT * FROM airports WHERE airport_code = 'ORD';")->fetchArray(SQLITE3_ASSOC);

        return new Response(
            200,
            $row
        );
    }
}