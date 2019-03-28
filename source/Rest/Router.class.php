<?php

namespace Rest;

use Rest\AndypointTypes\RequestType;
use SQLite3;
use Utils\CSVConverter;

/**
 * Keeps track of all existing endpoints for the API, and executes the right endpoint for a given path. To add an
 * endpoint to this router, call the method registerEndpoint.
 */
class Router
{
    /** @var Endpoint[] The string-indexed array of endpoints registered with this router. */
    private $endpoints = [];

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
	 * @param string $user_address The address that the client connected from.
     */
    public function respond(string $uri, string $request_type, array $headers, string $user_address)
    {
        $start_time = microtime(true);
        $endpoint = $this->getMatchingEndpointForURI($uri);

        if ($endpoint === null) {
            $response = $this->endpointNotFound();
        } else {
        	$request_type_int = RequestType::getType($request_type);
            $response = $endpoint->getResponse(
                $request_type_int,
                $this->extractURIParameters($uri, $endpoint->getUri()),
                $uri
            );
			$this->logUserRequest($user_address, $endpoint->getUri(), $request_type_int);
        }

        // This is an easy place to insert a link back to oneself.
        $return_payload = [
            'content' => $response->getPayload(),
            'links' => $response->getLinks(),
            'response_time' => microtime(true) - $start_time
        ];
        http_response_code($response->getCode());
        $this->outputFormattedResponse($this->globalizeLinks($return_payload), $headers);
    }

    /**
     * What to do when the client tries to access an endpoint that cannot be found.
     */
    private function endpointNotFound(): Response
    {
        return new ErrorResponse(
            404,
            'Resource not found.',
            [],
            [
                'available_resources' => array_map(function (Endpoint $endpoint): string {
                    return HOST_NAME . API_NAME . $endpoint->getUri();
                }, $this->endpoints)
            ]
        );
    }

    /**
     * Recursively applies the host name to all links, so that the links are ready for output.
     *
     * @param array $array
     * @return array
     */
    private function globalizeLinks(array $array): array
    {
        $globalized_links = $array;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if ($key === 'links') {
                    $globalized_links['links'] = $this->globalizeLinksRecursive($value);
                } else {
                    $globalized_links[$key] = $this->globalizeLinks($value);
                }
            }
        }
        return $globalized_links;
    }

    /**
     * Recursively traverse an array and append the host name to any values.
     *
     * @param array $array The array to traverse.
     * @return array
     */
    private function globalizeLinksRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->globalizeLinks($value);
            } else {
                $array[$key] = HOST_NAME . API_NAME . $value;
            }
        }
        return $array;
    }

    /**
     * Outputs the given return_payload to the client, formatted depending on the client's headers.
     * @param array $return_payload The array of data to send to the client.
     * @param array $headers The list of headers the client sent.
     */
    private function outputFormattedResponse(array $return_payload, array $headers)
    {
        if (isset($headers['Accept']) && $headers['Accept'] === 'text/csv') {
            header('Content-Type: text/csv');
            $response = CSVConverter::arrayToCsv($return_payload['content']);
        } else {
            header('Content-Type: application/json');
            $response = json_encode($return_payload, JSON_UNESCAPED_SLASHES);
        }
        echo $response;
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
        $uri_segments = $this->getSegmentsFromURI(parse_url($uri, PHP_URL_PATH));
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

	/**
	 * Logs this user's request to the API.
	 *
	 * @param string $user_address The address of the user, as indicated by the remote address value
	 * found in the SERVER super global.
	 * @param string $endpoint_uri The URI which was requested.
	 * @param int $request_type The type of request.
	 */
    private function logUserRequest(string $user_address, string $endpoint_uri, int $request_type)
	{
		$db = new SQLite3(DB_NAME);
		$insert_stmt = $db->prepare("INSERT OR IGNORE INTO users (address) VALUES (:user_address);");
		$insert_stmt->bindValue(':user_address', $user_address);
		$result = $insert_stmt->execute();
		// Quit if an error occurred.
		if (!$result) {
			return;
		}
		$user_id = $db->querySingle("SELECT id FROM users WHERE address = '" . $user_address . "';");
		$insert_data_stmt = $db->prepare("INSERT INTO user_requests (user_id, endpoint_uri, request_type) VALUES (:uid, :e_uri, :rt);");
		$insert_data_stmt->bindValue(':uid', $user_id);
		$insert_data_stmt->bindValue(':e_uri', $endpoint_uri);
		$insert_data_stmt->bindValue(':rt', $request_type);
		$insert_data_stmt->execute();
	}
}
