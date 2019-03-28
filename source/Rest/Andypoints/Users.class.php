<?php

namespace Rest\Andypoints;

use Rest\Pagination\PaginatedEndpoint;

/**
 * Endpoint for a collection of users.
 */
class Users extends PaginatedEndpoint
{
	const LOCATION = '/users';

	public function __construct()
	{
		parent::__construct(static::LOCATION);
	}

	protected function getResponseColumnNames(): array
    {
        return [
            'users.id',
            'users.address',
            '
(SELECT SUM(cnt)
FROM (
  SELECT endpoint_uri, COUNT(*) AS cnt
  FROM user_requests
  WHERE user_id = users.id
  GROUP BY endpoint_uri
  )) AS request_count
',
            '
(SELECT endpoint_uri
FROM user_requests
WHERE user_id = users.id
GROUP BY endpoint_uri
ORDER BY COUNT(id) DESC
LIMIT 1) AS most_requested_endpoint
'
        ];
    }

    /**
	 * @return string The name of the table that this endpoint uses, including any joins if desired.
	 */
	protected function getTableDeclaration(): string
	{
		return 'users';
	}

	/**
	 * @param array $resource_item The resource item to get the identifier for. This provides context if needed.
	 * @return string The name of the identifier which can be used to get a single resource from this endpoint.
	 */
	protected function getResourceIdentifierName(array $resource_item): string
	{
		return '/' . $resource_item['id'];
	}
}
