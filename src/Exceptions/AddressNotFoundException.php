<?php

namespace Naif\Saudiaddress\Exceptions;

class AddressNotFoundException extends SaudiAddressException
{
    /**
     * @param float $lat
     * @param float $lng
     * @return static
     */
    public static function forCoordinates(float $lat, float $lng)
    {
        return new static(
            sprintf('No address found for coordinates [%s, %s].', $lat, $lng)
        );
    }

    /**
     * @param string $shortAddress
     * @return static
     */
    public static function forShortAddress(string $shortAddress)
    {
        return new static(
            sprintf('No address found for short address [%s].', $shortAddress)
        );
    }
}
