<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\Pagination\PaginatedEndpoint;

class Airports extends PaginatedEndpoint implements GetRequest
{
    const LOCATION = '/airports';

    public function __construct()
    {
        parent::__construct(static::LOCATION);
    }

    /**
     * Since the user will not need the internal id, only give out the relevant columns.
     *
     * @return array
     */
    protected function getResponseColumnNames(): array
    {
        return [
            'airport_code',
            'airport_name'
        ];
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