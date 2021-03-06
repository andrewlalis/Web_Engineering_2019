<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\Pagination\ConditionBuilder;
use Rest\Pagination\PaginatedEndpoint;

class Carriers extends PaginatedEndpoint implements GetRequest
{
    const LOCATION = '/carriers';

    public function __construct()
    {
        parent::__construct(self::LOCATION);
    }

    protected function getResponseColumnNames(): array
    {
        return [
            'carrier_code',
            'carrier_name'
        ];
    }

    /**
     *
     * @param array $path_args
     * @param array $args
     * @return ConditionBuilder
     */
    protected function getConditionBuilder(array $path_args, array $args): ConditionBuilder
    {
        $builder = new ConditionBuilder();
        $builder->addConjunctIfArrayKeysExist(
            "carrier_code IN (SELECT carrier_code FROM airport_carrier WHERE airport_code = :airport_code)",
            ['airport_code'],
            $args
        );
        return $builder;
    }

    /**
     * @param array $resource_item
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected function getResourceIdentifierName(array $resource_item): string
    {
        return '/' . $resource_item['carrier_code'];
    }

    /**
     * @return string
     */
    protected function getTableDeclaration(): string
    {
        return 'carriers';
    }
}
