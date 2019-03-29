<?php

namespace Rest\Andypoints\Statistics;

use Rest\Andypoints\Statistics;
use Rest\Response;

/**
 * Endpoint for minutes delayed, which is a child of statistics.
 */
class MinutesDelayed extends Statistics
{
    const LOCATION = parent::LOCATION . '/minutes_delayed';

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
                'carrier',
                'security',
                'total',
                'national_aviation_system'
            ]
        );
    }

    protected function getTableDeclaration(): string
    {
        return parent::getTableDeclaration() . "
            LEFT JOIN statistics_minutes_delayed
                ON statistics_minutes_delayed.statistic_id = statistics.id
        ";
    }

    public function post(array $path_args, array $data): Response
    {
        return parent::postChild('statistics_minutes_delayed', $data);
    }

    public function delete(array $path_args, array $data): Response
    {
        return parent::deleteChild('statistics_minutes_delayed', $data);
    }

    public function patch(array $path_args, array $data): Response
    {
        return parent::patchChild('statistics_minutes_delayed', $data);
    }
}