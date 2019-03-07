<?php

namespace Rest;

use Rest\EndpointTypes\GetRequest;
use Rest\EndpointTypes\RequestType;
use SQLite3;

/**
 * Represents an endpoint for the API, or where a request and its parameters are processed.
 */
abstract class Endpoint
{
    /** @var string */
    private $uri;

    /** @var SQLite3 */
    private $db;

    /**
     * Constructs a new endpoint at the given uri.
     * @param string $uri The uri that this endpoint exists at. The URI must be formatted as follows:
     * /collection_name/{id}
     * /collection_name
     * /collection_name/{id}/child_item
     * /collection_name/{id}/child_items_collection/{child_id}
     *  - Note that path variables are identified by ASCII characters.
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;
        $this->db = new SQLite3(DB_NAME);
    }

    /**
     * Gets this endpoint's response to a request.
     * @param int $request_type The type of request (GET, POST, etc.)
     * @param array $uri_parameters An array of parameters as prescribed by this endpoint's uri.
     * @return Response A response object, ready to be sent back to the client.
     */
    public function getResponse(int $request_type, array $uri_parameters): Response
    {
        switch ($request_type) {
            case RequestType::GET:
                if ($this instanceof GetRequest) {
                    return $this->get($uri_parameters);
                }
                break;
        }
        return new Response(400, [
            'Unsupported request'
        ]);
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return SQLite3
     */
    public function getDb(): SQLite3
    {
        return $this->db;
    }
}