<?php

namespace Rest\AndypointTypes;

use Rest\Response;

/**
 * Endpoints which implement this interface must define some functionality for when a PATCH request is received.
 */
interface PatchRequest
{
    /**
     * Responds to a PATCH request.
     *
     * @param array $path_args A string-indexed list of path arguments as they are defined by the endpoint's constructor
     * @param array $data The array of data containing things to patch at this endpoint.
     * @return Response A response to the request.
     */
    public function patch(array $path_args, array $data): Response;
}