<?php

namespace Rest;

use Rest\EndpointTypes\RequestType;

/**
 * Keeps track of all existing endpoints for the API, and executes the right endpoint for a given path. To add an
 * endpoint to this router, call the method registerEndpoint.
 */
class Router
{
    /** @var Endpoint[] The string-indexed array of endpoints registered with this router. */
    private $endpoints = [];

    public function __construct()
    {
    }

    /**
     * Registers a new endpoint so that requests may be sent to it.
     * @param Endpoint $endpoint An instance of a responder which is responsible for responding to requests to the
     * given endpoint.
     */
    public function registerEndpoint(Endpoint $endpoint)
    {
        $this->endpoints[] = $endpoint;
    }

    /**
     * Sends the appropriate response back to the client.
     * @param string $uri The URI requested by the client.
     * @param string $request_type The request type (GET, POST, PATCH, etc.)
     * @param array $headers The headers the client has given.
     */
    public function respond(string $uri, string $request_type, array $headers)
    {
        $endpoint = $this->getMatchingEndpointForURI($uri);

        if ($endpoint === null) {
            echo 'Invalid endpoint!' . PHP_EOL;
            http_response_code(400);
            return;
        }

        $response = $endpoint->getResponse(
            RequestType::getType($request_type),
            $this->extractURIParameters($uri, $endpoint->getUri())
        );

        $return_payload = [
            'content' => $response->getPayload(),
            'links' => [
                'self' => $uri
            ]
        ];

        http_response_code($response->getCode());
        header('Content-Type: application/json');
        echo json_encode($return_payload);
    }

    /**
     * Finds an endpoint which matches the given URI.
     * @param string $uri The uri which the client has supplied.
     * @return Endpoint|null The endpoint which matches, or null.
     */
    private function getMatchingEndpointForURI(string $uri)
    {
        $segment_count = count($this->getSegmentsFromURI($uri));
        foreach ($this->endpoints as $endpoint) {
            // First check if the segments are the same, and if not, skip this endpoint.
            $endpoint_segment_count = count($this->getSegmentsFromURI($endpoint->getUri()));
            if ($segment_count != $endpoint_segment_count) {
                continue;
            }

            $pattern = '/' . $this->getEndpointURIRegex($endpoint->getUri()) . '/';
            preg_match($pattern, $uri, $matches);
            if ($matches) {
                return $endpoint;
            }
        }
        return null;
    }

    /**
     * Converts an endpoint uri into a regex pattern.
     * @param string $uri The endpoint's uri.
     * @return string A regex string in which all path variables have been replaced with an ascii pattern.
     */
    private function getEndpointURIRegex(string $uri): string
    {
        return implode('\/', array_map(function (string $segment): string {
            preg_match("/{([a-zA-Z]|\d)+(_([a-zA-Z]|\d)+)*}/", $segment, $matches);
            if ($matches) {
                return '([a-zA-Z]|\d)+(_([a-zA-Z]|\d)+)*';
            } else {
                return $segment;
            }
        }, $this->getSegmentsFromURI($uri)));
    }

    /**
     * Splits a URI into an array of segments.
     * @param string $uri The uri to split.
     * @return array An array of segments (which are not empty).
     */
    private function getSegmentsFromURI(string $uri): array
    {
        $segments = [];
        foreach (explode('/', $uri) as $segment) {
            if (!empty($segment)) {
                $segments[] = $segment;
            }
        }
        return $segments;
    }

    /**
     * Extracts a string-indexed array of parameters as they are found in the uri.
     * @param string $uri A uri which has been matched to the given endpoint uri.
     * @param string $endpoint_uri The uri of the endpoint which which will be used with the above uri.
     * @return array A string-indexed array of uri parameters, where each index is one specified in the endpoint's
     * constructor.
     */
    private function extractURIParameters(string $uri, string $endpoint_uri): array
    {
        $uri_segments = $this->getSegmentsFromURI($uri);
        $endpoint_segments = $this->getSegmentsFromURI($endpoint_uri);

        $vars = [];
        for ($i = 0; $i < count($uri_segments); $i++) {
            preg_match('/{([a-zA-Z]|\d)+(_([a-zA-Z]|\d)+)*}/', $endpoint_segments[$i], $matches);
            if (isset($matches[0])) {
                $variable_name = substr($matches[0], 1, strlen($matches[0]) - 2);
                $vars[$variable_name] = $uri_segments[$i];
            }
        }
        return $vars;
    }
}