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
}
