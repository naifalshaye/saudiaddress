<?php

namespace Naif\Saudiaddress;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Naif\Saudiaddress\Exceptions\AddressNotFoundException;
use Naif\Saudiaddress\Exceptions\ApiRequestException;
use Naif\Saudiaddress\Exceptions\InvalidConfigurationException;
use Naif\Saudiaddress\Exceptions\InvalidResponseException;

class SaudiAddress
{
    /** @var ClientInterface */
    protected $client;

    /** @var string */
    protected $baseUrl;

    /** @var string Base host URL (e.g. https://apina.address.gov.sa) */
    protected $host;

    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $format;

    /** @var string */
    protected $defaultLanguage;

    /**
     * @param ClientInterface $client
     * @param array $config
     *
     * @throws InvalidConfigurationException
     */
    public function __construct(ClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->baseUrl = rtrim($config['url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->format = $config['format'] ?? 'JSON';
        $this->defaultLanguage = $config['language'] ?? 'A';

        $parsed = parse_url($this->baseUrl);
        $this->host = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        $this->validateConfig();
    }

    /**
     * Get a list of all regions.
     *
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function regions(string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $response = $this->request('/lookup/regions', [
            'language' => $lang,
        ]);

        return $this->extractField($response, 'Regions', '/lookup/regions');
    }

    /**
     * Get a list of cities, optionally filtered by region.
     *
     * @param int $regionId Pass -1 for all cities
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function cities(int $regionId = -1, string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $response = $this->request('/lookup/cities', [
            'regionid' => $regionId,
            'language' => $lang,
        ]);

        return $this->extractField($response, 'Cities', '/lookup/cities');
    }

    /**
     * Get a list of districts within a city.
     *
     * @param int $cityId
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function districts(int $cityId, string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $response = $this->request('/lookup/districts', [
            'cityid' => $cityId,
            'language' => $lang,
        ]);

        return $this->extractField($response, 'Districts', '/lookup/districts');
    }

    /**
     * Get address details by geographic coordinates (reverse geocoding).
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return object
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     * @throws AddressNotFoundException
     */
    public function geoCode(float $lat, float $lng, string $lang = null)
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $endpoint = '/Address/address-geocode';
        $response = $this->request($endpoint, [
            'lat' => $lat,
            'long' => $lng,
            'language' => $lang,
        ]);

        $addresses = $this->extractField($response, 'Addresses', $endpoint);

        if (empty($addresses)) {
            throw AddressNotFoundException::forCoordinates($lat, $lng);
        }

        return $addresses[0];
    }

    /**
     * Verify an address by building number, post code, and additional number.
     *
     * @param int $buildingNumber
     * @param int $postCode
     * @param int $additionalNumber
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return bool
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function verify(int $buildingNumber, int $postCode, int $additionalNumber, string $lang = null): bool
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $endpoint = '/Address/address-verify';
        $response = $this->request($endpoint, [
            'buildingnumber' => $buildingNumber,
            'zipcode' => $postCode,
            'additionalnumber' => $additionalNumber,
            'language' => $lang,
        ]);

        return (bool) $this->extractField($response, 'addressfound', $endpoint);
    }

    // ---------------------------------------------------------------
    // Address Search
    // ---------------------------------------------------------------

    /**
     * Free-text address search.
     *
     * @param string $query Address search string
     * @param int $page Page number (10 results per page)
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function freeTextSearch(string $query, int $page = 1, string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $response = $this->request('/address/address-free-text', [
            'addressstring' => $query,
            'page' => $page,
            'language' => $lang,
        ]);

        return $this->extractField($response, 'Addresses', '/address/address-free-text');
    }

    /**
     * Fixed-parameter address search.
     *
     * @param array $params Search parameters (CityId, DistrictId, BuildingNumber, ZipCode, AdditionalNumber, CityName, DistrictName, StreetName)
     * @param int $page Page number (10 results per page)
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function fixedSearch(array $params, int $page = 1, string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $queryParams = array_merge($params, [
            'page' => $page,
            'language' => $lang,
        ]);

        $response = $this->request('/address/address-fixed-params', $queryParams);

        return $this->extractField($response, 'Addresses', '/address/address-fixed-params');
    }

    /**
     * Bulk address search (up to 10 addresses).
     *
     * @param array $addresses Array of address strings (max 10)
     * @param int $page Page number (10 results per page)
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function bulkSearch(array $addresses, int $page = 1, string $lang = null): array
    {
        if (count($addresses) > 10) {
            throw new InvalidArgumentException('Bulk search accepts a maximum of 10 addresses.');
        }

        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $addressString = implode(' | ; ', $addresses);

        $response = $this->request('/address/address-bulk', [
            'addressstring' => $addressString,
            'page' => $page,
            'language' => $lang,
        ]);

        return $this->extractField($response, 'Addresses', '/address/address-bulk');
    }

    // ---------------------------------------------------------------
    // Short Address
    // ---------------------------------------------------------------

    /**
     * Look up a national address by short address code.
     *
     * Short address format: 4 letters followed by 4 digits (e.g. ABCD1234).
     *
     * @param string $shortAddress
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws ApiRequestException
     * @throws InvalidResponseException
     * @throws AddressNotFoundException
     */
    public function shortAddress(string $shortAddress, string $lang = null): array
    {
        $this->validateShortAddress($shortAddress);

        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $path = '/NationalAddressByShortAddress/NationalAddressByShortAddress';
        $response = $this->requestAbsolute($path, [
            'shortaddress' => $shortAddress,
            'language' => $lang,
        ]);

        $addresses = $this->extractField($response, 'Addresses', $path);

        if (empty($addresses)) {
            throw AddressNotFoundException::forShortAddress($shortAddress);
        }

        return $addresses;
    }

    /**
     * Verify whether a short address code is valid.
     *
     * @param string $shortAddress
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return bool
     *
     * @throws InvalidArgumentException
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function verifyShortAddress(string $shortAddress, string $lang = null): bool
    {
        $this->validateShortAddress($shortAddress);

        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $path = '/NationalAddressByShortAddress/NationalAddressByShortAddress';
        $response = $this->requestAbsolute($path, [
            'shortaddress' => $shortAddress,
            'language' => $lang,
        ]);

        $addresses = $this->extractField($response, 'Addresses', $path);

        return !empty($addresses);
    }

    // ---------------------------------------------------------------
    // POI / Points of Interest
    // ---------------------------------------------------------------

    /**
     * Free-text POI search.
     *
     * @param string $query Service/POI search string
     * @param int $page Page number (10 results per page)
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function poiFreeTextSearch(string $query, int $page = 1, string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $response = $this->request('/address/poi-free-text', [
            'servicestring' => $query,
            'page' => $page,
            'language' => $lang,
        ]);

        return $this->extractField($response, 'Addresses', '/address/poi-free-text');
    }

    /**
     * Fixed-parameter POI search.
     *
     * @param string $query Service search string
     * @param int $regionId Region ID
     * @param array $params Optional: CityId, DistrictId, StreetName
     * @param int $page Page number (10 results per page)
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function poiFixedSearch(string $query, int $regionId, array $params = [], int $page = 1, string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $queryParams = array_merge($params, [
            'servicestring' => $query,
            'regionid' => $regionId,
            'page' => $page,
            'language' => $lang,
        ]);

        $response = $this->request('/address/poi-fixed-params', $queryParams);

        return $this->extractField($response, 'Addresses', '/address/poi-fixed-params');
    }

    /**
     * Find nearest POI by coordinates.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param float $radius Search radius in km (default 0.5)
     * @param int $page Page number (10 results per page)
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function nearestPoi(float $lat, float $lng, float $radius = 0.5, int $page = 1, string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $response = $this->request('/address/poi-nearest', [
            'lat' => $lat,
            'long' => $lng,
            'radius' => $radius,
            'page' => $page,
            'language' => $lang,
        ]);

        return $this->extractField($response, 'Addresses', '/address/poi-nearest');
    }

    // ---------------------------------------------------------------
    // Lookups
    // ---------------------------------------------------------------

    /**
     * Get service categories for POI search.
     *
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function serviceCategories(string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $response = $this->request('/lookup/service-categories', [
            'language' => $lang,
        ]);

        return $this->extractField($response, 'ServiceCategories', '/lookup/service-categories');
    }

    /**
     * Get service sub-categories for a given category.
     *
     * @param int $categoryId
     * @param string|null $lang 'A' for Arabic, 'E' for English
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function serviceSubCategories(int $categoryId, string $lang = null): array
    {
        $lang = $lang ?? $this->defaultLanguage;
        $this->validateLanguage($lang);

        $response = $this->request('/lookup/services-sub-categories', [
            'servicecategoryid' => $categoryId,
            'language' => $lang,
        ]);

        return $this->extractField($response, 'ServiceSubCategories', '/lookup/services-sub-categories');
    }

    // ---------------------------------------------------------------
    // Address By Phone
    // ---------------------------------------------------------------

    /**
     * Send OTP to a mobile number for address-by-phone lookup.
     *
     * @param string $storeId Store/merchant ID
     * @param string $mobileNo Mobile number
     * @return object
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function sendOtp(string $storeId, string $mobileNo)
    {
        return $this->request('/AddressByPhoneWithPinCode/UPDS API/api/OTP/GetPinCode', [
            'StoreId' => $storeId,
            'MobileNo' => $mobileNo,
        ]);
    }

    /**
     * Get addresses associated with a phone number after OTP verification.
     *
     * @param string $storeId Store/merchant ID
     * @param string $mobileNo Mobile number
     * @param string $pinCode OTP pin code
     * @return array
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function addressByPhone(string $storeId, string $mobileNo, string $pinCode): array
    {
        $response = $this->request('/AddressByPhoneWithPinCode/UPDS API/api/OTP/GetAddresses', [
            'StoreId' => $storeId,
            'MobileNo' => $mobileNo,
            'PinCode' => $pinCode,
        ]);

        return $this->extractField($response, 'Addresses', '/AddressByPhoneWithPinCode/UPDS API/api/OTP/GetAddresses');
    }

    // ---------------------------------------------------------------
    // Utilities
    // ---------------------------------------------------------------

    /**
     * Get geographic extents for a feature layer.
     *
     * @param string $layerName One of: regions, cities, districts, streets, zipcodes
     * @param string $featureId
     * @return object
     *
     * @throws InvalidArgumentException
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    public function featureExtents(string $layerName, string $featureId)
    {
        $validLayers = ['regions', 'cities', 'districts', 'streets', 'zipcodes'];

        if (!in_array($layerName, $validLayers, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid layer name [%s]. Valid layers are: %s.', $layerName, implode(', ', $validLayers))
            );
        }

        return $this->request('/address/get-feature-extents', [
            'layername' => $layerName,
            'featureid' => $featureId,
        ]);
    }

    // ---------------------------------------------------------------
    // HTTP
    // ---------------------------------------------------------------

    /**
     * Make an HTTP GET request to the API.
     *
     * @param string $endpoint
     * @param array $params
     * @return object
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    protected function request(string $endpoint, array $params = [])
    {
        $params['format'] = $this->format;
        $params['api_key'] = $this->apiKey;
        $params['encode'] = 'utf8';

        $url = $this->baseUrl . $endpoint;

        try {
            $response = $this->client->request('GET', $url, [
                'query' => $params,
            ]);
        } catch (GuzzleException $e) {
            throw ApiRequestException::fromGuzzleException($endpoint, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw ApiRequestException::unexpectedStatusCode($endpoint, $statusCode);
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidResponseException::invalidJson($endpoint);
        }

        return $decoded;
    }

    /**
     * Make an HTTP GET request to an absolute path on the API host.
     *
     * Used for endpoints outside the v3.1 base path (e.g. short address).
     *
     * @param string $path Absolute path from host root
     * @param array $params
     * @return object
     *
     * @throws ApiRequestException
     * @throws InvalidResponseException
     */
    protected function requestAbsolute(string $path, array $params = [])
    {
        $params['format'] = $this->format;
        $params['api_key'] = $this->apiKey;
        $params['encode'] = 'utf8';

        $url = $this->host . $path;

        try {
            $response = $this->client->request('GET', $url, [
                'query' => $params,
            ]);
        } catch (GuzzleException $e) {
            throw ApiRequestException::fromGuzzleException($path, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw ApiRequestException::unexpectedStatusCode($path, $statusCode);
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidResponseException::invalidJson($path);
        }

        return $decoded;
    }

    /**
     * Extract a field from the API response.
     *
     * @param object $response
     * @param string $field
     * @param string $endpoint
     * @return mixed
     *
     * @throws InvalidResponseException
     */
    protected function extractField($response, string $field, string $endpoint)
    {
        if (!isset($response->{$field})) {
            throw InvalidResponseException::missingField($endpoint, $field);
        }

        return $response->{$field};
    }

    /**
     * Validate the configuration.
     *
     * @return void
     * @throws InvalidConfigurationException
     */
    protected function validateConfig()
    {
        if (empty($this->baseUrl)) {
            throw InvalidConfigurationException::apiUrlNotSet();
        }

        if (empty($this->apiKey)) {
            throw InvalidConfigurationException::apiKeyNotSet();
        }
    }

    /**
     * Validate the language parameter.
     *
     * @param string $lang
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateLanguage(string $lang)
    {
        if (!in_array($lang, ['A', 'E'], true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid language [%s]. Supported languages are: A (Arabic), E (English).', $lang)
            );
        }
    }

    /**
     * Validate a short address format (4 letters + 4 digits).
     *
     * @param string $shortAddress
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateShortAddress(string $shortAddress)
    {
        if (!preg_match('/^[A-Za-z]{4}[0-9]{4}$/', $shortAddress)) {
            throw new InvalidArgumentException(
                sprintf('Invalid short address [%s]. Format must be 4 letters followed by 4 digits (e.g. ABCD1234).', $shortAddress)
            );
        }
    }
}
