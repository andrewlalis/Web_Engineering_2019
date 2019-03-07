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

    public function __construct(int $code, array $payload)
    {
        $this->code = $code;
        $this->payload = $payload;
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
}