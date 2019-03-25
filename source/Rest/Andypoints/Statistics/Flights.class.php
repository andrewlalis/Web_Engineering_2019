<?php

namespace Rest\Andypoints\Statistics;

use Rest\Andypoints\Statistics;
use Rest\Response;

/**
 * Endpoint for statistics about flights concerning a carrier at an airport.
 */
class Flights extends Statistics
{
    const LOCATION = parent::LOCATION . '/flights';

    public function __construct()
    {
        parent::__construct(static::LOCATION);
    }

    protected function getResponseColumnNames(): array
    {
        return array_merge(
            parent::getResponseColumnNames(),
            [
                'cancelled',
                'on_time',
                'delayed',
                'diverted',
                'total'
            ]
        );
    }

    /**
     * Extend the parent statistic table declaration by joining with the flights table.
     *
     * @return string
     */
    protected function getTableDeclaration(): string
    {
        return parent::getTableDeclaration() . "
            LEFT JOIN statistics_flights
                ON statistics_flights.statistic_id = statistics.id
        ";
    }


    public function post(array $path_args, array $data): Response
    {
        return parent::postChild('statistics_flights', $data);
    }

    public function delete(array $path_args, array $data): Response
    {
        return parent::deleteChild('statistics_flights', $data);
    }
}