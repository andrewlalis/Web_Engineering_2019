<?php

namespace Rest\Endpoints;

use Rest\Endpoint;
use Rest\EndpointTypes\GetRequest;
use Rest\Response;

class Carriers extends Endpoint implements GetRequest
{
    public function __construct()
    {
        parent::__construct('/carriers');
    }

    public function get(array $args): Response
    {
        $results = $this->fetchCollectionWithQuery("SELECT * FROM carriers");
        $carriers = array_map(function (array $carrier_data): array {
            $carrier_data['links'] = [
                'self' => $this->getUri() . '/' . $carrier_data['carrier_code']
            ];
            return $carrier_data;
        }, $results);
        return new Response(
            200,
            $carriers
        );
    }
}