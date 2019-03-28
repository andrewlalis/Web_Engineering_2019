<?php

namespace Rest\AndypointTypes;

/**
 * Any endpoints which implement this interface must declare some set of parameters which are mandatory for operations
 */
interface MandatoryParameterRequest
{
    /**
     * @return array An array of strings, each representing a parameter that must be present in requests to the resource
     */
    public function getMandatoryParameters(): array;
}