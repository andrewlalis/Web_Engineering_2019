<?php

namespace Rest\EndpointTypes;

use Rest\Response;

interface GetRequest
{
    public function get(array $args): Response;
}