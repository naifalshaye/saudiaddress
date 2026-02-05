<?php

namespace Naif\Saudiaddress\Exceptions;

class InvalidConfigurationException extends SaudiAddressException
{
    /**
     * @return static
     */
    public static function apiKeyNotSet()
    {
        return new static(
            'The Saudi Address API key is not set. '
            . 'Please set the SAUDI_ADDRESS_API_KEY environment variable '
            . 'or publish the config and set it directly.'
        );
    }

    /**
     * @return static
     */
    public static function apiUrlNotSet()
    {
        return new static(
            'The Saudi Address API URL is not set. '
            . 'Please set the SAUDI_ADDRESS_API_URL environment variable.'
        );
    }
}
