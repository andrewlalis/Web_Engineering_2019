<?php

namespace Rest\Andypoints\Statistics;

use Rest\Andypoints\Statistics;
use Rest\ErrorResponse;
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
        $select_stmt = $this->getDb()->prepare("SELECT * FROM statistics_minutes_delayed WHERE statistic_id = :statistic_id;");
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
            'statistics_minutes_delayed',
            [
                'statistic_id' => ':statistic_id',
                'late_aircraft' => ':late_aircraft',
                'weather' => ':weather',
                'carrier' => ':carrier',
                'security' => ':security',
                'total' => ':total',
                'national_aviation_system' => ':national_aviation_system'
            ],
            [
                ':statistic_id' => $parent_id,
                ':late_aircraft' => $data['late_aircraft'] ?? 0,
                ':weather' => $data['weather'] ?? 0,
                ':carrier' => $data['carrier'] ?? 0,
                ':security' => $data['security'] ?? 0,
                ':total' => $data['total'] ?? 0,
                ':national_aviation_system' => $data['national_aviation_system'] ?? 0
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
        return parent::deleteChild('statistics_minutes_delayed', $data);
    }
}