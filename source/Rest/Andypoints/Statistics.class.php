<?php

namespace Rest\Andypoints;

use Rest\Andypoints\Statistics\Delays;
use Rest\Andypoints\Statistics\Flights;
use Rest\Andypoints\Statistics\MinutesDelayed;
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
     * Finds the id of a statistic identified by the given parameters.
     *
     * @param string $airport_code
     * @param string $carrier_code
     * @param int $year
     * @param int $month
     * @return int|null
     */
    private function getStatisticId(string $airport_code, string $carrier_code, int $year, int $month)
    {
        $select_statistic_stmt = $this->getDb()->prepare("
SELECT * FROM statistics
WHERE airport_id = (SELECT id FROM airports WHERE airport_code = :airport_code) AND
      carrier_id = (SELECT id FROM carriers WHERE carrier_code = :carrier_code) AND
      time_year = :time_year AND
      time_month = :time_month
");
        $select_statistic_stmt->bindValue(':airport_code', $airport_code);
        $select_statistic_stmt->bindValue(':carrier_code', $carrier_code);
        $select_statistic_stmt->bindValue(':time_year', $year);
        $select_statistic_stmt->bindValue(':time_month', $month);
        $result = $select_statistic_stmt->execute();
        if ($result === false) {
            return null;
        } else {
            return $result->fetchArray(SQLITE3_ASSOC)['id'];
        }
    }

    /**
     * Determines if a statistic identified by some parameters exists.
     *
     * @param string $airport_code
     * @param string $carrier_code
     * @param int $year
     * @param int $month
     * @return bool
     */
    private function statisticExists(string $airport_code, string $carrier_code, int $year, int $month): bool
    {
        return ($this->getStatisticId($airport_code, $carrier_code, $year, $month) !== null);
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
        // First check if this resource already exists.
       $exists = $this->statisticExists($data['airport_code'], $data['carrier_code'], $data['year'], $data['month']);
        if ($exists) {
            return new ErrorResponse(
                409,
                'Resource already exists.'
            );
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
     * A method which child endpoints of this statistics endpoint can use to implement POST. It first checks if the
     * parent resource exists, and then checks if the child exists, and if all goes well, inserts the new data.
     *
     * @param string $table_name The name of the table that the child class uses.
     * @param array $data The data to be posted. Each key in the data should be a valid column name in the table.
     *
     * @return Response
     */
    protected function postChild(string $table_name, array $data): Response
    {
        $parent_response = $this->post([], $data);
        // Check if there was a duplicate when inserting the parent. If the parent already exists, continue anyway.
        if ($parent_response instanceof ErrorResponse) {
            if ($parent_response->getCode() !== 409) {
                return $parent_response;
            } else {
                $parent_id = $parent_response->getContext()['id'];
            }
        } else {
            $parent_id = $parent_response->getPayload()['id'];
        }

        // Check if this resource exists.
        $select_stmt = $this->getDb()->prepare("SELECT * FROM " . $table_name . " WHERE statistic_id = :statistic_id;");
        $select_stmt->bindValue(':statistic_id', $parent_id);
        $result = $select_stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if (!empty($row)) {
            return new ErrorResponse(
                409,
                'Resource already exists.',
                [
                    'statistic_id' => $parent_id
                ]
            );
        }
        $placeholders = [];
        $placeholder_values = [];
        foreach ($data as $key => $value) {
            $placeholders[$key] = ':' . $key;
            $placeholder_values[$placeholders[$key]] = $value ?? 0;
        }
        $success = $this->insertIntoCollection(
            $table_name,
            array_merge(['statistic_id' => ':statistic_id'], $placeholders),
            array_merge([':statistic_id' => $parent_id], $placeholder_values)
        );

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
        $success = $this->deleteFromCollection(
            'statistics',
            [
                'airport_id = (SELECT id FROM airports WHERE airport_code = :airport_code)',
                'carrier_id = (SELECT id FROM carriers WHERE carrier_code = :carrier_code)',
                'time_year = :year AND time_month = :month'
            ],
            [
                ':airport_code' => $data['airport_code'],
                ':carrier_code' => $data['carrier_code'],
                ':year' => $data['year'],
                ':month' => $data['month']
            ]
        );

        if (!$success) {
            return new ErrorResponse(500, 'Failed to delete the specified resource.');
        } else {
            return new Response(204, [
                'message' => 'Resource deleted successfully.'
            ]);
        }
    }

    /**
     * Deletes a child statistic resource using a specialized query such that the client does not need to know about
     * arbitrary database indices.
     * @param string $table_name The name of the child's table.
     * @param array $data The request data, containing identifying information for the resource to delete.
     * @return Response Either an error response, or a successful deletion resulting in a normal response.
     */
    protected function deleteChild(string $table_name, array $data): Response
    {
        $success = $this->deleteFromCollection(
            $table_name,
            [
                'statistic_id = (SELECT id FROM statistics WHERE 
                airport_id = (SELECT id FROM airports WHERE airport_code = :airport_code) AND 
                carrier_id = (SELECT id FROM carriers WHERE carrier_code = :carrier_code) AND 
                time_year = :year AND time_month = :month)'
            ],
            [
                ':airport_code' => $data['airport_code'],
                ':carrier_code' => $data['carrier_code'],
                ':year' => $data['year'],
                ':month' => $data['month']
            ]
        );

        if (!$success) {
            return new ErrorResponse(500, 'Failed to delete the specified resource.');
        } else {
            return new Response(204, [
                'message' => 'Resource deleted successfully.'
            ]);
        }
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
        return new ErrorResponse(
            403,
            'You may not patch a statistical record, only a subset of the record.',
            [],
            [
                Flights::LOCATION,
                Delays::LOCATION,
                MinutesDelayed::LOCATION
            ]
        );
    }

    public function patchChild(string $table_name, array $data): Response
    {
        $exists = $this->statisticExists($data['airport_code'], $data['carrier_code'], $data['year'], $data['month']);
        if (!$exists) {
            return new ErrorResponse(
                404,
                'No resource identified by the specified parameters exists.'
            );
        }

        $statistic_id = $this->getStatisticId($data['airport_code'], $data['carrier_code'], $data['year'], $data['month']);
        $set_statements = [];
        $set_values = [
            ':statistic_id' => $statistic_id
        ];
        foreach ($data as $key => $value) {
            $set_statements[] = $key . ' = :' . $key;
            $set_values[':' . $key] = $value;
        }
        $sql = "UPDATE " . $table_name . " SET " . implode(', ', $set_statements) . " WHERE statistic_id = :statistic_id";
        $update_stmt = $this->getDb()->prepare($sql);
        foreach ($set_values as $placeholder => $value) {
            $update_stmt->bindValue($placeholder, $value);
        }
        $result = $update_stmt->execute();

        if ($result !== false) {
            return new Response(
                200,
                [
                    'message' => 'Statistics patched successfully.'
                ]
            );
        } else {
            return new ErrorResponse(
                500,
                'Could not patch the statistic.',
                [
                    'db_error' => $this->getDb()->lastErrorMsg(),
                    'db_error_code' => $this->getDb()->lastErrorCode()
                ]
            );
        }
    }

    /**
     * @return array An array of strings, each representing a parameter that must be present in requests to the resource
     */
    public function getMandatoryParameters(): array
    {
        return [
            'airport_code',
            'carrier_code',
            'year',
            'month'
        ];
    }
}