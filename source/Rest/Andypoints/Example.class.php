<?php

namespace Rest\Andypoints;

use Rest\Endpoint;
use Rest\AndypointTypes\GetRequest;
use Rest\Response;

class Example extends Endpoint implements GetRequest
{

    public function __construct()
    {
        parent::__construct('/example/{value}');
    }

    public function get(array $args): Response
    {
        return new Response(200, [
            'message' => 'Thank you for sending a value to the example endpoint!',
            'value' => $args['value'],
            'uri' => $this->getUri()
        ]);
    }
}