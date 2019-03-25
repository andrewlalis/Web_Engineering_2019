<?php

namespace Rest\AndypointTypes;

use Rest\Response;

/**
 * Endpoints which implement this interface must define behavior for when the client POSTs some data.
 */
interface PostRequest extends MandatoryParameterRequest
{
    /**
     * Responds to a POST request to this resource.
     *
     * @param array $path_args Any path arguments the client has provided.
     * @param array $data The post data the client has supplied.
     *
     * @return Response A response to this request. This contains both a response code, and an array of data to send
     * back to the client.
     */
    public function post(array $path_args, array $data): Response;
}