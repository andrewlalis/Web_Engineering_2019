<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\PostRequest;
use Rest\ErrorResponse;
use Rest\Pagination\ConditionBuilder;
use Rest\Pagination\PaginatedEndpoint;
use Rest\Response;

/**
 * Endpoint for all statistics, optionally filtered by an airport, carrier, month, year, or any combination of those.
 *
 * Some other endpoints extend this one by providing more specific details for each statistic entry.
 */
class Statistics extends PaginatedEndpoint implements PostRequest
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

    /**
     * Responds to a POST request to this resource.
     *
     * @param array $path_args Any path arguments the client has provided.
     * @param array $data The post data the client has supplied.
     *
     * @return Response A response to this request. This contains both a response code, and an array of data to send
     * back to the client.
     */
    public function post(array $path_args, array $data): Response
    {
        // First check if all the required parameters are available.
        if (!(isset($data['airport_id'])
            && isset($data['carrier_id'])
            && isset($data['year'])
            && isset($data['month']))) {
            return new ErrorResponse(400, 'Bad request. Not all identifying information was given for a new statistic.');
        }

        $time_label = $data['year'] . '/' . $data['month'];

        $insert_statistic_statement = $this->getDb()->prepare("
INSERT INTO statistics (airport_id, carrier_id, time_label, time_year, time_month)
VALUES (:airport_id, :carrier_id, :time_label, :time_year, :time_month)
");

        $insert_statistic_statement->bindValue(':airport_id', $data['airport_id']);
        $insert_statistic_statement->bindValue(':carrier_id', $data['carrier_id']);
        $insert_statistic_statement->bindValue(':time_label', $time_label);
        $insert_statistic_statement->bindValue(':time_year', $data['year']);
        $insert_statistic_statement->bindValue(':time_month', $data['month']);

        $result = $insert_statistic_statement->execute();

        if ($result === false) {
            return new ErrorResponse(
                500,
                'Database error occurred.',
                [
                    'db_error' => $this->getDb()->lastErrorMsg(),
                    'db_error_code' => $this->getDb()->lastErrorCode()
                ]
            );
        }

        if (!empty($result)) {
            return new Response(
                201,
                ['message' => 'Resource created.', 'id' => $this->getDb()->lastInsertRowID()]
            );
        }
    }
}