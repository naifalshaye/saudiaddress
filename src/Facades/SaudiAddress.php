<?php

namespace Naif\Saudiaddress\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array regions(string $lang = null)
 * @method static array cities(int $regionId = -1, string $lang = null)
 * @method static array districts(int $cityId, string $lang = null)
 * @method static object geoCode(float $lat, float $lng, string $lang = null)
 * @method static bool verify(int $buildingNumber, int $postCode, int $additionalNumber, string $lang = null)
 *
 * @see \Naif\Saudiaddress\SaudiAddress
 */
class SaudiAddress extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'saudi-address';
    }
}
