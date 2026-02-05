<?php

namespace Naif\Saudiaddress\Exceptions;

class InvalidResponseException extends SaudiAddressException
{
    /**
     * @param string $endpoint
     * @return static
     */
    public static function invalidJson(string $endpoint)
    {
        return new static(
            sprintf('The API response from [%s] could not be decoded as JSON.', $endpoint)
        );
    }

    /**
     * @param string $endpoint
     * @param string $field
     * @return static
     */
    public static function missingField(string $endpoint, string $field)
    {
        return new static(
            sprintf(
                'The API response from [%s] is missing the expected field [%s].',
                $endpoint,
                $field
            )
        );
    }
}
