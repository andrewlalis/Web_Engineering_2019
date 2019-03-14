<?php

namespace Rest\AndypointTypes;

use Rest\Response;

interface GetRequest
{
    public function get(array $args): Response;
}