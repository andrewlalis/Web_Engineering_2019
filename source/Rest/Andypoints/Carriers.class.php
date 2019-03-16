<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\Pagination\ConditionBuilder;
use Rest\Pagination\PaginatedEndpoint;

class Carriers extends PaginatedEndpoint implements GetRequest
{
    public function __construct()
    {
        parent::__construct('/carriers');
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
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected function getResourceIdentifierName(): string
    {
        return 'carrier_code';
    }

    /**
     * @return string
     */
    protected function getTableDeclaration(): string
    {
        return 'carriers';
    }
}
