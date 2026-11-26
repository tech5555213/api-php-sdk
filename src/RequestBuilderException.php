<?php

namespace Loamhill\Api;

final class RequestBuilderException extends \Exception
{
    /** @var string */
    private $method;

    /** @var array */
    private $errors;

    /**
     * @param string $message
     * @param int $responseCode HTTP status code (used as the exception code)
     * @param string $uri
     * @param array $errors Validation errors (HTTP 422): field => list of messages
     * @param \Exception|null $previous
     */
    public function __construct($message, $responseCode, $uri, $errors = [], $previous = null)
    {
        $this->method = $uri;
        $this->errors = $errors;

        parent::__construct($message, $responseCode, $previous);
    }

    /** @return string API path that failed */
    public function getMethod()
    {
        return $this->method;
    }

    /** @return array */
    public function getErrors()
    {
        return $this->errors;
    }
}
