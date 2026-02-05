<?php

namespace Naif\Saudiaddress\Exceptions;

use GuzzleHttp\Exception\GuzzleException;

class ApiRequestException extends SaudiAddressException
{
    /**
     * @param string $endpoint
     * @param \Throwable $previous
     * @return static
     */
    public static function fromGuzzleException(string $endpoint, $previous)
    {
        return new static(
            sprintf('API request to [%s] failed: %s', $endpoint, $previous->getMessage()),
            (int) $previous->getCode(),
            $previous
        );
    }

    /**
     * @param string $endpoint
     * @param int $statusCode
     * @return static
     */
    public static function unexpectedStatusCode(string $endpoint, int $statusCode)
    {
        return new static(
            sprintf('API request to [%s] returned unexpected status code: %d', $endpoint, $statusCode)
        );
    }
}
