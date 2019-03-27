<?php

/**
 * This router handles requests for resources such as HTML, CSS, JS, and any images that might be needed.
 */
class ResourceRouter
{
    const MIME_TYPES = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'text/javascript',
        'png' => 'image/png'
    ];

    /**
     * Responds to a request for a resource located at the particular URI.
     *
     * @param string $uri The URI of the resource to fetch.
     */
    public function respond(string $uri)
    {
        // Check if the uri ends with a slash, and if so, append the standard index.html to it.
        if (substr($uri, -1) === '/') {
            $uri .= 'index.html';
        }

        // Apply the global sub-domain name for all resources.
        $uri = TEMPLATE_NAME . $uri;

        $mime_type =$this->getMimeTypeFromFileExtension($uri);
        // If the mime type could not be determined, show an error.
        if (!empty($mime_type)) {
            header('Content-type: ' . $mime_type);
            http_response_code(200);
            echo file_get_contents($uri);
        }
    }

    private function getMimeTypeFromFileExtension($uri) {
        $matches = [];
        $result = preg_match("/\..+/", $uri, $matches);
        if ($result && array_key_exists(substr($matches[0], -(strlen($matches[0]) - 1)), static::MIME_TYPES)) {
            return static::MIME_TYPES[substr($matches[0], -(strlen($matches[0]) - 1))];
        }
        return null;
    }
}