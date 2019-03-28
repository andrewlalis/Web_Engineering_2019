<?php

namespace Rest\Andypoints\Users;

use Rest\Pagination\ConditionBuilder;
use Rest\Pagination\Conjunct;
use Rest\Pagination\PaginatedEndpoint;

/**
 * Endpoint for the collection of request records for any particular user.
 */
class Requests extends PaginatedEndpoint
{
	const LOCATION = '/users/{id}/requests';

	public function __construct()
	{
		parent::__construct(static::LOCATION);
	}

	/**
	 * @return string The name of the table that this endpoint uses, including any joins if desired.
	 */
	protected function getTableDeclaration(): string
	{
		return 'user_requests';
	}

	/**
	 * @param array $resource_item The resource item to get the identifier for. This provides context if needed.
	 * @return string The name of the identifier which can be used to get a single resource from this endpoint.
	 */
	protected function getResourceIdentifierName(array $resource_item): string
	{
		return '?';
	}

	protected function getConditionBuilder(array $path_args, array $args): ConditionBuilder
	{
		$builder = new ConditionBuilder();
		$builder->addConjunct(
			new Conjunct(
				"user_id = :user_id",
				[
					'user_id' => $path_args['id']
				]
			)
		);
		return $builder;
	}
}
