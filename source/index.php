/<?php

$uri = $_SERVER['REQUEST_URI'];
$headers = getallheaders();

// The content type to return to the client.
$requested_content_type = $headers['Accept'];

var_dump($uri);
var_dump($headers);