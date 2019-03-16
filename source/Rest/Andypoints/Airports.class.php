<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\Pagination\PaginatedEndpoint;

class Airports extends PaginatedEndpoint implements GetRequest
{

    public function __construct()
    {
        parent::__construct('/airports');
    }

    /**
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected function getResourceIdentifierName(): string
    {
        return 'airport_code';
    }

    /**
     * @return string
     */
    protected function getTableDeclaration(): string
    {
        return 'airports';
    }
}