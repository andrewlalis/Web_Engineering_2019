<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\Endpoint;
use Rest\Response;

class Carriers extends Endpoint implements GetRequest
{
    public function __construct()
    {
        parent::__construct('/carriers');
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