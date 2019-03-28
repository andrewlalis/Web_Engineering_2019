<?php

namespace Rest\Andypoints\Users;

use Rest\AndypointTypes\GetRequest;
use Rest\Endpoint;
use Rest\ErrorResponse;
use Rest\Response;

/**
 * Represents a single user.
 */
class User extends Endpoint implements GetRequest
{
	const LOCATION = '/users/{id}';

	public function __construct()
	{
		parent::__construct(static::LOCATION);
	}

	/**
	 * Responds to a GET request to this resource.
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
		$user = $this->getDb()->query("SELECT * FROM users WHERE id = " . $path_args['id'])->fetchArray(SQLITE3_ASSOC);
		if ($user === false) {
			return new ErrorResponse(
				404,
				'No user found with id ' . $path_args['id']
			);
		}

		return new Response(
			200,
			$user,
			[
				'requests' => '/users/' . $user['id'] . '/requests'
			]
		);
	}
}
