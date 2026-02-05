<?php

namespace Naif\Saudiaddress\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Naif\Saudiaddress\Exceptions\AddressNotFoundException;
use Naif\Saudiaddress\Exceptions\ApiRequestException;
use Naif\Saudiaddress\Exceptions\InvalidConfigurationException;
use Naif\Saudiaddress\Exceptions\InvalidResponseException;
use Naif\Saudiaddress\SaudiAddress;
use PHPUnit\Framework\TestCase;

class SaudiAddressTest extends TestCase
{
    /** @var array */
    protected $defaultConfig = [
        'url' => 'https://apina.address.gov.sa/NationalAddress/v3.1',
        'api_key' => 'test-api-key',
        'format' => 'JSON',
        'language' => 'A',
    ];

    /**
     * Create a SaudiAddress instance with mocked HTTP responses.
     *
     * @param array $responses Guzzle mock responses
     * @param array $history Reference to capture request history
     * @param array $config Override config values
     * @return SaudiAddress
     */
    protected function createService(array $responses, array &$history = [], array $config = [])
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($history));
        $client = new Client(['handler' => $handlerStack]);

        return new SaudiAddress($client, array_merge($this->defaultConfig, $config));
    }

    /**
     * Parse query parameters from a request in the history.
     *
     * @param array $history
     * @param int $index
     * @return array
     */
    protected function getQueryParams(array $history, int $index = 0): array
    {
        $query = [];
        parse_str($history[$index]['request']->getUri()->getQuery(), $query);
        return $query;
    }

    // ---------------------------------------------------------------
    // regions()
    // ---------------------------------------------------------------

    public function testRegionsReturnsArrayOfRegions()
    {
        $body = json_encode([
            'Regions' => [
                ['Id' => '1', 'Name' => 'Riyadh'],
                ['Id' => '2', 'Name' => 'Makkah'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->regions();

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
    }

    public function testRegionsWithEnglishLanguage()
    {
        $body = json_encode(['Regions' => [['Id' => '1', 'Name' => 'Riyadh']]]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->regions('E');

        $params = $this->getQueryParams($history);
        $this->assertEquals('E', $params['language']);
    }

    public function testRegionsUsesDefaultLanguageFromConfig()
    {
        $body = json_encode(['Regions' => [['Id' => '1', 'Name' => 'Riyadh']]]);
        $history = [];

        $service = $this->createService(
            [new Response(200, [], $body)],
            $history,
            ['language' => 'E']
        );
        $service->regions();

        $params = $this->getQueryParams($history);
        $this->assertEquals('E', $params['language']);
    }

    // ---------------------------------------------------------------
    // cities()
    // ---------------------------------------------------------------

    public function testCitiesReturnsArrayOfCities()
    {
        $body = json_encode([
            'Cities' => [
                ['Id' => '3', 'Name' => 'Riyadh'],
                ['Id' => '4', 'Name' => 'Jeddah'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->cities();

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
    }

    public function testCitiesDefaultsToAllRegions()
    {
        $body = json_encode(['Cities' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->cities();

        $params = $this->getQueryParams($history);
        $this->assertEquals('-1', $params['regionid']);
    }

    public function testCitiesFiltersByRegion()
    {
        $body = json_encode(['Cities' => [['Id' => '3', 'Name' => 'Riyadh']]]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->cities(1);

        $params = $this->getQueryParams($history);
        $this->assertEquals('1', $params['regionid']);
    }

    // ---------------------------------------------------------------
    // districts()
    // ---------------------------------------------------------------

    public function testDistrictsReturnsArrayOfDistricts()
    {
        $body = json_encode([
            'Districts' => [
                ['Id' => '100', 'Name' => 'Al Hamra'],
                ['Id' => '101', 'Name' => 'Al Olaya'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->districts(3);

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
    }

    public function testDistrictsSendsCityIdInQuery()
    {
        $body = json_encode(['Districts' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->districts(42);

        $params = $this->getQueryParams($history);
        $this->assertEquals('42', $params['cityid']);
    }

    // ---------------------------------------------------------------
    // geoCode()
    // ---------------------------------------------------------------

    public function testGeoCodeReturnsAddressObject()
    {
        $body = json_encode([
            'Addresses' => [
                (object) [
                    'BuildingNumber' => '7596',
                    'Street' => 'Test Street',
                    'District' => 'Al Hamra',
                    'City' => 'Riyadh',
                    'PostCode' => '13216',
                ],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->geoCode(24.774265, 46.738586);

        $this->assertTrue(is_object($result));
        $this->assertEquals('7596', $result->BuildingNumber);
        $this->assertEquals('Riyadh', $result->City);
    }

    public function testGeoCodeSendsCorrectCoordinates()
    {
        $body = json_encode([
            'Addresses' => [(object) ['BuildingNumber' => '1']],
        ]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->geoCode(24.774265, 46.738586);

        $params = $this->getQueryParams($history);
        $this->assertEquals('24.774265', $params['lat']);
        $this->assertEquals('46.738586', $params['long']);
    }

    public function testGeoCodeThrowsAddressNotFoundForEmptyResults()
    {
        $body = json_encode(['Addresses' => []]);

        $service = $this->createService([new Response(200, [], $body)]);

        $this->expectException(AddressNotFoundException::class);
        $this->expectExceptionMessage('No address found for coordinates');
        $service->geoCode(0.0, 0.0);
    }

    // ---------------------------------------------------------------
    // verify()
    // ---------------------------------------------------------------

    public function testVerifyReturnsTrueForValidAddress()
    {
        $body = json_encode(['addressfound' => true]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->verify(7596, 13216, 2802);

        $this->assertTrue($result);
    }

    public function testVerifyReturnsFalseForInvalidAddress()
    {
        $body = json_encode(['addressfound' => false]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->verify(9999, 00000, 0000);

        $this->assertFalse($result);
    }

    public function testVerifySendsCorrectParameters()
    {
        $body = json_encode(['addressfound' => true]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->verify(7596, 13216, 2802);

        $params = $this->getQueryParams($history);
        $this->assertEquals('7596', $params['buildingnumber']);
        $this->assertEquals('13216', $params['zipcode']);
        $this->assertEquals('2802', $params['additionalnumber']);
    }

    // ---------------------------------------------------------------
    // Common request behavior
    // ---------------------------------------------------------------

    public function testApiKeyIsSentInQueryParameters()
    {
        $body = json_encode(['Regions' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->regions();

        $params = $this->getQueryParams($history);
        $this->assertEquals('test-api-key', $params['api_key']);
    }

    public function testFormatIsSentInQueryParameters()
    {
        $body = json_encode(['Regions' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->regions();

        $params = $this->getQueryParams($history);
        $this->assertEquals('JSON', $params['format']);
    }

    public function testRequestUrlIsConstructedCorrectly()
    {
        $body = json_encode(['Regions' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->regions();

        $uri = $history[0]['request']->getUri();
        $this->assertStringContainsString('/NationalAddress/v3.1/lookup/regions', (string) $uri);
    }

    // ---------------------------------------------------------------
    // Error handling
    // ---------------------------------------------------------------

    public function testThrowsApiRequestExceptionOnNetworkError()
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', 'test')),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $service = new SaudiAddress($client, $this->defaultConfig);

        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('API request to [/lookup/regions] failed');
        $service->regions();
    }

    public function testThrowsInvalidResponseExceptionOnInvalidJson()
    {
        $service = $this->createService([
            new Response(200, [], 'this is not json'),
        ]);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('could not be decoded as JSON');
        $service->regions();
    }

    public function testThrowsInvalidResponseExceptionOnMissingField()
    {
        $body = json_encode(['SomethingElse' => 'value']);

        $service = $this->createService([new Response(200, [], $body)]);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('missing the expected field [Regions]');
        $service->regions();
    }

    // ---------------------------------------------------------------
    // Configuration validation
    // ---------------------------------------------------------------

    public function testThrowsInvalidConfigurationExceptionWhenApiKeyEmpty()
    {
        $mock = new MockHandler([]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('API key is not set');
        new SaudiAddress($client, [
            'url' => 'https://example.com',
            'api_key' => '',
        ]);
    }

    public function testThrowsInvalidConfigurationExceptionWhenUrlEmpty()
    {
        $mock = new MockHandler([]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('API URL is not set');
        new SaudiAddress($client, [
            'url' => '',
            'api_key' => 'test-key',
        ]);
    }

    // ---------------------------------------------------------------
    // Language validation
    // ---------------------------------------------------------------

    public function testThrowsInvalidArgumentExceptionForInvalidLanguage()
    {
        $service = $this->createService([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language [X]');
        $service->regions('X');
    }

    public function testThrowsInvalidArgumentExceptionForLowercaseLanguage()
    {
        $service = $this->createService([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language [a]');
        $service->regions('a');
    }
}
