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
