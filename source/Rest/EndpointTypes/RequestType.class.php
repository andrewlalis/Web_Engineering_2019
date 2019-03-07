<?php

namespace Rest\EndpointTypes;


class RequestType
{
    const NONE = 0;
    const GET = 1;
    const POST = 2;
    const PATCH = 3;
    const PUT = 4;
    const DELETE = 5;

    /**
     * Gets the integer type for a specific HTTP verb.
     * @param string $verb
     * @return int
     */
    public static function getType(string $verb): int
    {
        $verb = strtoupper($verb);
        switch ($verb){
            case 'GET':
                return static::GET;
            case 'POST':
                return static::POST;
            case 'PATCH':
                return static::PATCH;
            case 'PUT':
                return static::PUT;
            case 'DELETE':
                return static::DELETE;
            default:
                return static::NONE;
        }
    }
}