<?php

namespace Rest\Andypoints\Statistics;

use Rest\Andypoints\Statistics;

/**
 * Endpoint for statistics about numbers of delays.
 */
class Delays extends Statistics
{
    const LOCATION = '/statistics/delays';

    public function __construct()
    {
        parent::__construct(static::LOCATION);
    }

    protected function getResponseColumnNames(): array
    {
        return array_merge(
            parent::getResponseColumnNames(),
            [
                'late_aircraft',
                'weather',
                'security',
                'national_aviation_system',
                'carrier'
            ]
        );
    }

    protected function getTableDeclaration(): string
    {
        return parent::getTableDeclaration() . "
            LEFT JOIN statistics_delays
                ON statistics_delays.statistic_id = statistics.id
        ";
    }
}