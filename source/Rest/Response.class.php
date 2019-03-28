<?php

namespace Rest;

/**
 * Represents a response to an HTTP request. Contains a response code and a payload.
 */
class Response
{
    /** @var int */
    private $code;

    /** @var array */
    private $payload;

    /** @var array */
    private $links;

    public function __construct(int $code, array $payload, array $links = [])
    {
        $this->code = $code;
        $this->payload = $payload;
        $this->links = $links;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }
}