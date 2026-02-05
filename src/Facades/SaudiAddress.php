<?php

namespace Naif\Saudiaddress\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array regions(string $lang = null)
 * @method static array cities(int $regionId = -1, string $lang = null)
 * @method static array districts(int $cityId, string $lang = null)
 * @method static object geoCode(float $lat, float $lng, string $lang = null)
 * @method static bool verify(int $buildingNumber, int $postCode, int $additionalNumber, string $lang = null)
 * @method static array freeTextSearch(string $query, int $page = 1, string $lang = null)
 * @method static array fixedSearch(array $params, int $page = 1, string $lang = null)
 * @method static array bulkSearch(array $addresses, int $page = 1, string $lang = null)
 * @method static array shortAddress(string $shortAddress, string $lang = null)
 * @method static bool verifyShortAddress(string $shortAddress, string $lang = null)
 * @method static array poiFreeTextSearch(string $query, int $page = 1, string $lang = null)
 * @method static array poiFixedSearch(string $query, int $regionId, array $params = [], int $page = 1, string $lang = null)
 * @method static array nearestPoi(float $lat, float $lng, float $radius = 0.5, int $page = 1, string $lang = null)
 * @method static array serviceCategories(string $lang = null)
 * @method static array serviceSubCategories(int $categoryId, string $lang = null)
 * @method static object sendOtp(string $storeId, string $mobileNo)
 * @method static array addressByPhone(string $storeId, string $mobileNo, string $pinCode)
 * @method static object featureExtents(string $layerName, string $featureId)
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
