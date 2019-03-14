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
        $results = $this->fetchCollectionWithQuery("SELECT * FROM airports;");
        $airports = array_map(function (array $airport_data): array {
            $airport_data['links'] = [
                'self' => $this->getUri() . '/' . $airport_data['airport_code']
            ];
            return $airport_data;
        }, $results);

        return new Response(
            200,
            $airports
        );
    }
}