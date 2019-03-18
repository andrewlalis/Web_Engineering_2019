<?php

namespace Rest\Andypoints;

use Rest\Pagination\ConditionBuilder;
use Rest\Pagination\PaginatedEndpoint;

/**
 * Endpoint for all statistics, optionally filtered by an airport, carrier, month, year, or any combination of those.
 *
 * Some other endpoints extend this one by providing more specific details for each statistic entry.
 */
class Statistics extends PaginatedEndpoint
{
    const LOCATION = '/statistics';

    public function __construct(string $uri)
    {
        // Note the use of 'self' here, so that children may redefine LOCATION.
        parent::__construct($uri);
    }

    protected function getResponseColumnNames(): array
    {
        return [
            'statistics.id',
            'airports.airport_code',
            'carriers.carrier_code',
            'statistics.time_year AS year',
            'statistics.time_month AS month'
        ];
    }

    /**
     * @return string The name of the table that this endpoint uses, including any joins if desired.
     */
    protected function getTableDeclaration(): string
    {
        return "
        statistics
        LEFT JOIN airports
            ON airport_id = airports.id
        LEFT JOIN carriers
            ON carrier_id = carriers.id
        ";
    }

    /**
     * @param array $resource_item The resource item to get the identifier for. This provides context if needed.
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected function getResourceIdentifierName(array $resource_item): string
    {
        return '?airport_code=' . $resource_item['airport_code']
            . '&carrier_code=' . $resource_item['carrier_code']
            . '&year=' . $resource_item['year']
            . '&month=' . $resource_item['month'];
    }

    protected function getAdditionalResourceLinks(array $resource_item): array
    {
        return [
            'airport' => Airports::LOCATION . '/' . $resource_item['airport_code'],
            'carrier' => Carriers::LOCATION . '/' . $resource_item['carrier_code']
        ];
    }

    protected function getConditionBuilder(array $path_args, array $args): ConditionBuilder
    {
        $builder = new ConditionBuilder();
        $builder->addConjunctIfArrayKeysExist(
            "airport_code = :airport_code",
            ['airport_code'],
            $args
        );
        $builder->addConjunctIfArrayKeysExist(
            "carrier_code = :carrier_code",
            ['carrier_code'],
            $args
        );
        $builder->addConjunctIfArrayKeysExist(
            "time_year = :year",
            ['year'],
            $args
        );
        $builder->addConjunctIfArrayKeysExist(
            'time_month = :month',
            ['month'],
            $args
        );
        return $builder;
    }
}