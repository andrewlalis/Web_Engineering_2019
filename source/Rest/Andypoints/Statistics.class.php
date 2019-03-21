<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\DeleteRequest;
use Rest\AndypointTypes\PatchRequest;
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
class Statistics extends PaginatedEndpoint implements PostRequest, PatchRequest, DeleteRequest
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
        if (!(isset($data['airport_code'])
            && isset($data['carrier_code'])
            && isset($data['year'])
            && isset($data['month']))) {
            return new ErrorResponse(400, 'Bad request. Not all identifying information was given for a new statistic.');
        }
        $time_label = $data['year'] . '/' . $data['month'];
        $success = $this->insertIntoCollection(
            'statistics',
            [
                'airport_id' => '(SELECT id FROM airports WHERE airport_code = :airport_code)',
                'carrier_id' => '(SELECT id FROM carriers WHERE carrier_code = :carrier_code)',
                'time_label' => ':time_label',
                'time_year' => ':time_year',
                'time_month' => ':time_month'
            ],
            [
                ':airport_code' => $data['airport_code'],
                ':carrier_code' => $data['carrier_code'],
                ':time_label' => $time_label,
                ':time_year' => $data['year'],
                ':time_month' => $data['month']
            ]
        );

        // Check if the execution failed for whatever reason.
        if ($success === false) {
            return new ErrorResponse(
                500,
                'Database error occurred.',
                [
                    'db_error' => $this->getDb()->lastErrorMsg(),
                    'db_error_code' => $this->getDb()->lastErrorCode()
                ]
            );
        }

        return new Response(
            201,
            [
                'message' => 'Resource created.',
                'id' => $this->getDb()->lastInsertRowID()
            ]
        );
    }

    /**
     * Responds to a DELETE request at this endpoint (Does not mean that something must deleted, just that a response
     * is required).
     *
     * @param array $path_args Any path arguments provided, as specified by the endpoint's constructor.
     * @param array $data Any additional data sent from the client.
     * @return Response The response to the client's request.
     */
    public function delete(array $path_args, array $data): Response
    {
        $delete_statement = $this->getDb()->prepare("
DELETE FROM statistics
WHERE
      airport_id = (SELECT id FROM airports WHERE airport_code = :airport_code)
      AND carrier_id = (SELECT id FROM carriers WHERE carrier_code = :carrier_code)
      AND time_year = :year AND time_month = :month;
");
    }

    /**
     * Responds to a PATCH request.
     *
     * @param array $path_args A string-indexed list of path arguments as they are defined by the endpoint's constructor
     * @param array $data The array of data containing things to patch at this endpoint.
     * @return Response A response to the request.
     */
    public function patch(array $path_args, array $data): Response
    {
        // TODO: Implement patch() method.
    }
}