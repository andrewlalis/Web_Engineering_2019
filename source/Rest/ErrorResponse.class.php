<?php

namespace Rest;

/**
 * Error responses are a standardized way of returning error messages throughout the Andyframework.
 */
class ErrorResponse extends Response
{
    /**
     * Constructs a new error message response.
     *
     * @param int $code The code for the error response.
     * @param string $error_message The message to be given back to the client about the error.
     * @param array $context Additional information about the error, if necessary.
     * @param array $links An array of links, if needed.
     */
    public function __construct(int $code, string $error_message, array $context = [], array $links = [])
    {
        parent::__construct(
            $code,
            [
                'error_message' => $error_message,
                'context' => $context
            ],
            $links
        );
    }

    /**
     * @return array The context for this error response.
     */
    public function getContext(): array
    {
        return $this->getPayload()['context'];
    }
}