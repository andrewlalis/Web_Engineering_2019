<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\PaginatedEndpoint;
use Rest\Response;

class Airports extends PaginatedEndpoint implements GetRequest
{

    public function __construct()
    {
        parent::__construct('/airports');
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
        $result = $this->fetchCollectionWithQuery(
            'SELECT * FROM airports LIMIT :limit_value OFFSET :offset_value;',
            [
                ':limit_value' => $limit,
                ':offset_value' => $offset
            ]
        );

        return new Response(
            200,
            $result,
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
        $statement = $this->getDb()->prepare('SELECT COUNT(airport_code) AS cnt FROM airports;');
        $result = $statement->execute();
        return $result->fetchArray(SQLITE3_ASSOC)['cnt'];
    }

    /**
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected function getResourceIdentifierName(): string
    {
        return 'airport_code';
    }
}