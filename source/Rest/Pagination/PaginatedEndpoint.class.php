<?php

namespace Rest\Pagination;

use Rest\AndypointTypes\GetRequest;
use Rest\Endpoint;
use Rest\Response;

/**
 * A paginated endpoint is one in which a page and limit can be supplied to request only a subset of the resources.
 *
 * Child classes should only need to implement getTableDeclaration and getResourceIdentifierName to add a new paginated
 * endpoint, but it may also be desirable to implement getConditionBuilder if the endpoint can be filtered by user-
 * supplied parameters, or getResponseColumnNames if the response should only contain some columns and not all.
 */
abstract class PaginatedEndpoint extends Endpoint implements GetRequest
{
    /** @var int If no page is supplied, this is the default page that will be assumed. */
    const DEFAULT_PAGE = 1;

    /** @var int If no limit is supplied, this is the default limit for the number of resources per page. */
    const DEFAULT_LIMIT = 10;

    /** @var int The absolute maximum amount of resources that can be returned per page. */
    const MAX_LIMIT = 50;

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
        // Either get the user's defined page, or the default values.
        $page = $args['page'] ?? static::DEFAULT_PAGE;
        $limit = min($args['limit'] ?? static::DEFAULT_LIMIT, static::MAX_LIMIT);

        // Compute offset using some QUICK MATHS.
        $offset = ($page - 1) * $limit;

        // These two things are responsible for the querying of the database.
        $page_count = ceil($this->getTotalResourceCount($path_args, $args) / $limit);
        $response = $this->getPaginatedResponse($path_args, $args, $offset, $limit);

        // Wrap the original response in this new response, which augments the list of links for extra info.
        $paginated_response = new Response(
            $response->getCode(),
            array_map([$this, 'addResourceSpecificLinks'], $response->getPayload()),
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
    private function addResourceSpecificLinks(array $resource_item): array
    {
        $resource_item['links'] = array_merge(
            [
                'self' => parse_url($this->getRawURI(), PHP_URL_PATH) . $this->getResourceIdentifierName($resource_item)
            ],
            $this->getAdditionalResourceLinks($resource_item)
        );

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
        if (strpos($uri, 'page=', 0) === false) {
            // Check if there are any query parameters.
            if (strpos($uri, '?', 0) === false) {
                $uri .= '?page=' . $current_page;
            } else {
                $uri .= '&page=' . $current_page;
            }
        }

        // Add the 'limit' parameter if it doesn't exist yet.
        if (strpos($uri, 'limit=', 0) === false) {
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

        // Get the list of columns that should be selected for, or all of them.
        $column_names_list = $this->getResponseColumnNames();
        if (empty($column_names_list)) {
            $column_names = '*';
        } else {
            $column_names = implode(', ', $column_names_list);
        }

        // Build the SQL statement here.
        $sql = "SELECT " . $column_names . " FROM " . $this->getTableDeclaration();
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

        $results = $this->fetchCollection($sql, $values);

        return new Response(
            200,
            $results,
            []
        );
    }

    /**
     * Determines how many total resources are available at this endpoint, in total, when taking into account filtering
     * by any user-provided arguments.
     *
     * @param array $path_args The path arguments for this request.
     * @param array $args The arguments for this request.
     * @return int The total number of resources that exist at this endpoint regardless of pagination.
     */
    protected function getTotalResourceCount(array $path_args, array $args): int
    {
        $builder = $this->getConditionBuilder($path_args, $args);
        $sql = "SELECT COUNT(*) as cnt FROM " . $this->getTableDeclaration();
        if ($builder->hasConjuncts()) {
            $sql .= " WHERE " . $builder->buildConditionalClause();
        }
        $sql .= ';';
        $result = $this->fetchCollection($sql, $builder->buildPlaceholderValues());
        return $result[0]['cnt'];
    }

    /**
     * @param array $resource_item The resource item to get links for.
     * @return array Any string-indexed additional links to resources that are related to the current resource.
     */
    protected function getAdditionalResourceLinks(array $resource_item): array
    {
        return [];
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
     * @return array The list of columns that should be selected when getting resources. By default this is empty, which
     * means all columns will be returned.
     */
    protected function getResponseColumnNames(): array
    {
        return [];
    }

    /**
     * @return string The name of the table that this endpoint uses, including any joins if desired.
     */
    protected abstract function getTableDeclaration(): string;

    /**
     * @param array $resource_item The resource item to get the identifier for. This provides context if needed.
     * @return string The name of the identifier which can be used to get a single resource from this endpoint.
     */
    protected abstract function getResourceIdentifierName(array $resource_item): string;
}