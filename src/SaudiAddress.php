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
}
