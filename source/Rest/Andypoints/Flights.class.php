<?php

namespace Rest\Andypoints;

use Rest\Pagination\ConditionBuilder;
use Rest\Pagination\Conjunct;
use Rest\Pagination\PaginatedEndpoint;

class Flights extends PaginatedEndpoint
{

    public function __construct()
    {
        parent::__construct('/flights/{airport_code}/{carrier_code}');
    }

    protected function getResponseColumnNames(): array
    {
        return [
            'statistic_id',
            'airport_code',
            'airport_name',
            'carrier_code',
            'carrier_name',
            'cancelled',
            'on_time',
            'delayed',
            'diverted',
            'total',
            'time_year as year',
            'time_month as month'
        ];
    }

    /**
     * @return string The name of the table that this endpoint uses, including any joins if desired.
     */
    protected function getTableDeclaration(): string
    {
        return "
        statistics_flights
        LEFT JOIN statistics
            ON statistics_flights.statistic_id = statistics.id
        LEFT JOIN airports
            ON statistics.airport_id = airports.id
        LEFT JOIN carriers
            ON statistics.carrier_id = carriers.id
        ";
    }

    /**
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected function getResourceIdentifierName(): string
    {
        return 'statistic_id';
    }

    protected function getConditionBuilder(array $path_args, array $args): ConditionBuilder
    {
        $builder = new ConditionBuilder();
        $builder->addConjunct(new Conjunct("airport_code = :airport_code", ['airport_code' => $path_args['airport_code']]));
        $builder->addConjunct(new Conjunct("carrier_code = :carrier_code", ['carrier_code' => $path_args['carrier_code']]));
        return $builder;
    }
}