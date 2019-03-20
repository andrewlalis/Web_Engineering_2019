<?php

namespace Rest\AndypointTypes;

use Rest\Response;

/**
 * Endpoints which implement this interface must define functionality for deleting a resource.
 */
interface DeleteRequest
{
    /**
     * Responds to a DELETE request at this endpoint (Does not mean that something must deleted, just that a response
     * is required).
     *
     * @param array $path_args Any path arguments provided, as specified by the endpoint's constructor.
     * @param array $data Any additional data sent from the client.
     * @return Response The response to the client's request.
     */
    public function delete(array $path_args, array $data): Response;
}