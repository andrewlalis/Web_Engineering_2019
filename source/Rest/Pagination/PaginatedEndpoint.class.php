<?php

namespace Rest\Pagination;

use Rest\AndypointTypes\GetRequest;
use Rest\Endpoint;
use Rest\Response;

/**
 * A paginated endpoint is one in which a page and limit can be supplied to request only a subset of the resources. The
 */
abstract class PaginatedEndpoint extends Endpoint implements GetRequest
{
    const DEFAULT_PAGE = 1;
    const DEFAULT_LIMIT = 10;

    /**
     * Responds to a GET request to this resource. Since this is a paginated endpoint, it takes a page and limit
     * parameter, and also augments the links that a child provides by injecting its own page navigation links.
     *
     * @param array $path_args Any path arguments provided, with the same name as declared by the URI used to construct
     * the endpoint which implements this interface.
     * @param array $args A string-indexed list of arguments provided by the client.
     *
     * @return Response A response to this request. This contains both a response code, and an array of data to send
     * back to the client.
     */
    public function get(array $path_args, array $args): Response
    {
        $page = $args['page'] ?? self::DEFAULT_PAGE;
        $limit = $args['limit'] ?? self::DEFAULT_LIMIT;
        $offset = ($page - 1) * $limit;
        $page_count = ceil($this->getTotalResourceCount($path_args, $args) / $limit);

        $response = $this->getPaginatedResponse($path_args, $args, $offset, $limit);

        // Wrap the original response in this new response, which augments the list of links.
        $paginated_response = new Response(
            $response->getCode(),
            array_map([$this, 'addResourceSelfLink'], $response->getPayload()),
            array_merge($response->getLinks(), $this->generateRelativePageLinks($page, $limit, $page_count))
        );

        return $paginated_response;
    }

    /**
     * Adds a 'self' link to a given resource item, based on what the identifier is for this resource.
     *
     * @param array $resource_item The single resource to add a link to. In paginated endpoints, many resources will be
     * returned in an array, so this is simply one of those arrays.
     * @return array The same array, with a 'self' link added.
     */
    private function addResourceSelfLink(array $resource_item): array
    {
        $resource_item['links'] = [
            'self' => $this->getUri() . '/' . $resource_item[$this->getResourceIdentifierName()]
        ];

        return $resource_item;
    }

    /**
     * Generates some links to different pages in this paginated endpoint.
     *
     * @param int $current_page The page the client has currently requested.
     * @param int $limit The limit for resources per page.
     * @param int $page_count The total number of pages available for this resource.
     * @return array A string-indexed array of links.
     */
    private function generateRelativePageLinks(int $current_page, int $limit, int $page_count): array
    {
        $uri = $this->getRawURI();

        // Add the 'page' parameter if it doesn't exist yet.
        if (strpos($uri, 'page=', strlen($this->getUri())) === false) {
            // Check if there are any query parameters.
            if (strpos($uri, '?', strlen($this->getUri())) === false) {
                $uri .= '?page=' . $current_page;
            } else {
                $uri .= '&page=' . $current_page;
            }
        }

        // Add the 'limit' parameter if it doesn't exist yet.
        if (strpos($uri, 'limit=', strlen($this->getUri())) === false) {
            $uri .= '&limit=' . $limit;
        }

        // Now, all uri's should contain the 'page=' and 'limit=' parameters, that all paginated endpoints use.
        $paginated_links = [
            'self' => $uri,
            'first_page' => str_replace('page=' . $current_page, 'page=' . 1, $uri),
            'last_page' => str_replace('page=' . $current_page, 'page=' . $page_count, $uri)
        ];
        if ($current_page > 1) {
            $paginated_links['previous_page'] = str_replace('page=' . $current_page, 'page=' . ($current_page - 1), $uri);
        }
        if ($current_page < $page_count) {
            $paginated_links['next_page'] = str_replace('page=' . $current_page, 'page=' . ($current_page + 1), $uri);
        }
        return $paginated_links;
    }

    /**
     * Gets a ConditionBuilder which has had some Conjuncts added to it. Child classes should extend this method if they
     * wish to filter any requests based on user-provided arguments.
     *
     * @param array $path_args
     * @param array $args
     * @return ConditionBuilder
     */
    protected function getConditionBuilder(array $path_args, array $args): ConditionBuilder
    {
        return new ConditionBuilder();
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
        $builder = $this->getConditionBuilder($path_args, $args);
        $sql = "SELECT * FROM " . $this->getTableDeclaration();
        if ($builder->hasConjuncts()) {
            $sql .= " WHERE " . $builder->buildConditionalClause();
        }
        $sql .= " LIMIT :limit_value OFFSET :offset_value;";
        $values = array_merge(
            $builder->buildPlaceholderValues(),
            [
                ':limit_value' => $limit,
                ':offset_value' => $offset
            ]
        );

        $results = $this->fetchCollectionWithQuery($sql, $values);

        return new Response(
            200,
            $results,
            []
        );
    }

    /**
     * @param array $path_args The path arguments for this request.
     * @param array $args The arguments for this request.
     * @return int The total number of resources that exist at this endpoint regardless of pagination.
     */
    protected function getTotalResourceCount(array $path_args, array $args): int
    {
        $builder = $this->getConditionBuilder($path_args, $args);
        $sql = "SELECT COUNT(" . $this->getResourceIdentifierName() . ") as cnt FROM " . $this->getTableDeclaration();
        if ($builder->hasConjuncts()) {
            $sql .= " WHERE " . $builder->buildConditionalClause();
        }
        $sql .= ';';
        $result = $this->fetchCollectionWithQuery($sql, $builder->buildPlaceholderValues());
        return $result[0]['cnt'];
    }

    /**
     * @return string
     */
    protected abstract function getTableDeclaration(): string;

    /**
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected abstract function getResourceIdentifierName(): string;
}