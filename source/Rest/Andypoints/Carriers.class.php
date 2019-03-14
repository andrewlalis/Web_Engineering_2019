<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\PaginatedEndpoint;
use Rest\Response;

class Carriers extends PaginatedEndpoint implements GetRequest
{
    public function __construct()
    {
        parent::__construct('/carriers');
    }

    /**
     * Responds to a paginated request, returning a subsection of all possible resources.
     *
     * @param array $path_args The path arguments from the request URI.
     * @param array $args The arguments to this request.
     * @param int $offset The offset for the request (Used for SQL).
     * @param int $limit The number of resources per page.
     * @return Response A response containing only the specified page of resources.
     */
    protected function getPaginatedResponse(array $path_args, array $args, int $offset, int $limit): Response
    {
        if (isset($args['airport_code'])) {
            $results = $this->fetchCollectionWithQuery(
"
SELECT *
FROM carriers
WHERE carrier_code IN (
  SELECT carrier_code
  FROM airport_carrier
  WHERE airport_code = :airport_code
)
LIMIT :limit_value OFFSET :offset_value;
",
                [
                    ':limit_value' => $limit,
                    ':offset_value' => $offset,
                    ':airport_code' => $args['airport_code']
                ]
            );
        } else {
            $results = $this->fetchCollectionWithQuery(
"
SELECT *
FROM carriers
LIMIT :limit_value OFFSET :offset_value;
",
                [
                    ':limit_value' => $limit,
                    ':offset_value' => $offset
                ]
            );
        }


        return new Response(
            200,
            $results,
            []
        );
    }

    /**
     * @param array $path_args
     * @param array $args
     * @return int The total number of resources that exist at this endpoint regardless of pagination.
     */
    protected function getTotalResourceCount(array $path_args, array $args): int
    {
        if (isset($args['airport_code'])) {
            return $this->fetchCollectionWithQuery(
"
SELECT COUNT(carrier_code) AS cnt
FROM carriers
WHERE carrier_code IN (
  SELECT carrier_code
  FROM airport_carrier
  WHERE airport_code = :airport_code
);
",
                [
                    ':airport_code' => $args['airport_code']
                ]
            )[0]['cnt'];
        } else {
            return $this->fetchCollectionWithQuery(
"
SELECT COUNT(carrier_code) AS cnt
FROM carriers;
"
            )[0]['cnt'];
        }
    }

    /**
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected function getResourceIdentifierName(): string
    {
        return 'carrier_code';
    }
}
