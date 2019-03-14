<?php

namespace Rest\Endpoints\Carriers;


use Rest\Endpoint;
use Rest\EndpointTypes\GetRequest;
use Rest\Response;

class Carrier extends Endpoint implements GetRequest
{
    public function __construct()
    {
        parent::__construct('/carriers/{code}');
    }

    public function get(array $args): Response
    {
        $code = filter_var($args['code'], FILTER_SANITIZE_STRING);

        $statement = $this->getDb()->prepare("SELECT * FROM carriers WHERE carrier_code = :carrier_code;");
        $statement->bindValue(':carrier_code', $code);
        $result = $statement->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        return new Response(
            200,
            $row
        );
    }
}