<?php

namespace Rest\Andypoints\Statistics;

use Rest\Andypoints\Statistics;
use Rest\ErrorResponse;
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
        $parent_response = parent::post($path_args, $data);
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
        $select_stmt = $this->getDb()->prepare("SELECT * FROM statistics_flights WHERE statistic_id = :statistic_id;");
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

        $success = $this->insertIntoCollection(
            'statistics_flights',
            [
                'statistic_id' => ':statistic_id',
                'cancelled' => ':cancelled',
                'on_time' => ':on_time',
                'delayed' => ':on_time',
                'diverted' => ':diverted',
                'total' => ':total'
            ],
            [
                ':statistic_id' => $parent_id,
                ':cancelled' => $data['cancelled'] ?? 0,
                ':on_time' => $data['on_time'] ?? 0,
                ':delayed' => $data['delayed'] ?? 0,
                ':diverted' => $data['diverted'] ?? 0,
                ':total' => $data['total'] ?? 0
            ]
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

    public function delete(array $path_args, array $data): Response
    {
        return parent::deleteChild('statistics_flights', $data);
    }
}