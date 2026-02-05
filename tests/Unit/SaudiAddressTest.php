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

    // ---------------------------------------------------------------
    // encode=utf8 in all requests
    // ---------------------------------------------------------------

    public function testEncodeUtf8SentInAllRequests()
    {
        $body = json_encode(['Regions' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->regions();

        $params = $this->getQueryParams($history);
        $this->assertEquals('utf8', $params['encode']);
    }

    public function testEncodeUtf8SentInShortAddressRequests()
    {
        $body = json_encode(['Addresses' => [['BuildingNumber' => '1']]]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->shortAddress('ABCD1234');

        $params = $this->getQueryParams($history);
        $this->assertEquals('utf8', $params['encode']);
    }

    // ---------------------------------------------------------------
    // freeTextSearch()
    // ---------------------------------------------------------------

    public function testFreeTextSearchReturnsAddresses()
    {
        $body = json_encode([
            'Addresses' => [
                ['BuildingNumber' => '1234', 'Street' => 'King Fahd'],
                ['BuildingNumber' => '5678', 'Street' => 'Olaya'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->freeTextSearch('Riyadh');

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
    }

    public function testFreeTextSearchSendsCorrectParams()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->freeTextSearch('Riyadh', 2, 'E');

        $params = $this->getQueryParams($history);
        $this->assertEquals('Riyadh', $params['addressstring']);
        $this->assertEquals('2', $params['page']);
        $this->assertEquals('E', $params['language']);
    }

    // ---------------------------------------------------------------
    // fixedSearch()
    // ---------------------------------------------------------------

    public function testFixedSearchReturnsAddresses()
    {
        $body = json_encode([
            'Addresses' => [['BuildingNumber' => '1234']],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->fixedSearch(['CityId' => 3]);

        $this->assertTrue(is_array($result));
        $this->assertCount(1, $result);
    }

    public function testFixedSearchSendsCorrectParams()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->fixedSearch(['CityId' => 3, 'ZipCode' => 13216], 2, 'E');

        $params = $this->getQueryParams($history);
        $this->assertEquals('3', $params['CityId']);
        $this->assertEquals('13216', $params['ZipCode']);
        $this->assertEquals('2', $params['page']);
        $this->assertEquals('E', $params['language']);
    }

    // ---------------------------------------------------------------
    // bulkSearch()
    // ---------------------------------------------------------------

    public function testBulkSearchReturnsAddresses()
    {
        $body = json_encode([
            'Addresses' => [
                ['BuildingNumber' => '1234'],
                ['BuildingNumber' => '5678'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->bulkSearch(['Riyadh', 'Jeddah']);

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
    }

    public function testBulkSearchFormatsAddressString()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->bulkSearch(['Riyadh', 'Jeddah', 'Dammam']);

        $params = $this->getQueryParams($history);
        $this->assertEquals('Riyadh | ; Jeddah | ; Dammam', $params['addressstring']);
    }

    public function testBulkSearchRejectsMoreThan10Addresses()
    {
        $service = $this->createService([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maximum of 10 addresses');
        $service->bulkSearch(array_fill(0, 11, 'address'));
    }

    // ---------------------------------------------------------------
    // shortAddress()
    // ---------------------------------------------------------------

    public function testShortAddressReturnsAddresses()
    {
        $body = json_encode([
            'Addresses' => [
                ['BuildingNumber' => '7596', 'City' => 'Riyadh'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->shortAddress('ABCD1234');

        $this->assertTrue(is_array($result));
        $this->assertCount(1, $result);
    }

    public function testShortAddressUsesCorrectEndpoint()
    {
        $body = json_encode([
            'Addresses' => [['BuildingNumber' => '1']],
        ]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->shortAddress('ABCD1234');

        $uri = (string) $history[0]['request']->getUri();
        $this->assertStringContainsString('/NationalAddressByShortAddress/NationalAddressByShortAddress', $uri);
        $this->assertStringNotContainsString('/NationalAddress/v3.1', $uri);
    }

    public function testShortAddressValidatesFormat()
    {
        $service = $this->createService([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid short address');
        $service->shortAddress('INVALID');
    }

    public function testShortAddressThrowsNotFoundForEmptyResults()
    {
        $body = json_encode(['Addresses' => []]);

        $service = $this->createService([new Response(200, [], $body)]);

        $this->expectException(AddressNotFoundException::class);
        $this->expectExceptionMessage('short address [ABCD1234]');
        $service->shortAddress('ABCD1234');
    }

    // ---------------------------------------------------------------
    // verifyShortAddress()
    // ---------------------------------------------------------------

    public function testVerifyShortAddressReturnsTrue()
    {
        $body = json_encode([
            'Addresses' => [['BuildingNumber' => '1']],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->verifyShortAddress('ABCD1234');

        $this->assertTrue($result);
    }

    public function testVerifyShortAddressReturnsFalse()
    {
        $body = json_encode(['Addresses' => []]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->verifyShortAddress('ZZZZ9999');

        $this->assertFalse($result);
    }

    // ---------------------------------------------------------------
    // poiFreeTextSearch()
    // ---------------------------------------------------------------

    public function testPoiFreeTextSearchReturnsResults()
    {
        $body = json_encode([
            'Addresses' => [
                ['ServiceName' => 'Hospital'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->poiFreeTextSearch('hospital');

        $this->assertTrue(is_array($result));
        $this->assertCount(1, $result);
    }

    public function testPoiFreeTextSearchSendsCorrectParams()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->poiFreeTextSearch('hospital', 2, 'E');

        $params = $this->getQueryParams($history);
        $this->assertEquals('hospital', $params['servicestring']);
        $this->assertEquals('2', $params['page']);
        $this->assertEquals('E', $params['language']);
    }

    // ---------------------------------------------------------------
    // poiFixedSearch()
    // ---------------------------------------------------------------

    public function testPoiFixedSearchReturnsResults()
    {
        $body = json_encode([
            'Addresses' => [
                ['ServiceName' => 'School'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->poiFixedSearch('school', 1);

        $this->assertTrue(is_array($result));
        $this->assertCount(1, $result);
    }

    public function testPoiFixedSearchSendsRegionId()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->poiFixedSearch('school', 5, ['CityId' => 3]);

        $params = $this->getQueryParams($history);
        $this->assertEquals('school', $params['servicestring']);
        $this->assertEquals('5', $params['regionid']);
        $this->assertEquals('3', $params['CityId']);
    }

    // ---------------------------------------------------------------
    // nearestPoi()
    // ---------------------------------------------------------------

    public function testNearestPoiReturnsResults()
    {
        $body = json_encode([
            'Addresses' => [
                ['ServiceName' => 'Pharmacy'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->nearestPoi(24.774265, 46.738586);

        $this->assertTrue(is_array($result));
        $this->assertCount(1, $result);
    }

    public function testNearestPoiSendsRadiusParam()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->nearestPoi(24.774265, 46.738586, 2.0);

        $params = $this->getQueryParams($history);
        $this->assertEquals('24.774265', $params['lat']);
        $this->assertEquals('46.738586', $params['long']);
        $this->assertEquals('2', $params['radius']);
    }

    public function testNearestPoiUsesDefaultRadius()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->nearestPoi(24.774265, 46.738586);

        $params = $this->getQueryParams($history);
        $this->assertEquals('0.5', $params['radius']);
    }

    // ---------------------------------------------------------------
    // serviceCategories()
    // ---------------------------------------------------------------

    public function testServiceCategoriesReturnsArray()
    {
        $body = json_encode([
            'ServiceCategories' => [
                ['Id' => 1, 'Name' => 'Health'],
                ['Id' => 2, 'Name' => 'Education'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->serviceCategories();

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
    }

    // ---------------------------------------------------------------
    // serviceSubCategories()
    // ---------------------------------------------------------------

    public function testServiceSubCategoriesReturnsArray()
    {
        $body = json_encode([
            'ServiceSubCategories' => [
                ['Id' => 10, 'Name' => 'Hospital'],
                ['Id' => 11, 'Name' => 'Clinic'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->serviceSubCategories(1);

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
    }

    public function testServiceSubCategoriesSendsCategoryId()
    {
        $body = json_encode(['ServiceSubCategories' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->serviceSubCategories(5, 'E');

        $params = $this->getQueryParams($history);
        $this->assertEquals('5', $params['servicecategoryid']);
        $this->assertEquals('E', $params['language']);
    }

    // ---------------------------------------------------------------
    // sendOtp()
    // ---------------------------------------------------------------

    public function testSendOtpReturnsResponse()
    {
        $body = json_encode([
            'Status' => 'Success',
            'Message' => 'OTP sent',
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->sendOtp('STORE001', '0501234567');

        $this->assertTrue(is_object($result));
        $this->assertEquals('Success', $result->Status);
    }

    public function testSendOtpSendsCorrectParams()
    {
        $body = json_encode(['Status' => 'Success']);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->sendOtp('STORE001', '0501234567');

        $params = $this->getQueryParams($history);
        $this->assertEquals('STORE001', $params['StoreId']);
        $this->assertEquals('0501234567', $params['MobileNo']);
    }

    // ---------------------------------------------------------------
    // addressByPhone()
    // ---------------------------------------------------------------

    public function testAddressByPhoneReturnsAddresses()
    {
        $body = json_encode([
            'Addresses' => [
                ['BuildingNumber' => '1234', 'City' => 'Riyadh'],
            ],
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->addressByPhone('STORE001', '0501234567', '1234');

        $this->assertTrue(is_array($result));
        $this->assertCount(1, $result);
    }

    public function testAddressByPhoneSendsCorrectParams()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->addressByPhone('STORE001', '0501234567', '9876');

        $params = $this->getQueryParams($history);
        $this->assertEquals('STORE001', $params['StoreId']);
        $this->assertEquals('0501234567', $params['MobileNo']);
        $this->assertEquals('9876', $params['PinCode']);
    }

    // ---------------------------------------------------------------
    // featureExtents()
    // ---------------------------------------------------------------

    public function testFeatureExtentsReturnsObject()
    {
        $body = json_encode([
            'xmin' => 34.5,
            'ymin' => 16.3,
            'xmax' => 55.6,
            'ymax' => 32.1,
        ]);

        $service = $this->createService([new Response(200, [], $body)]);
        $result = $service->featureExtents('regions', '1');

        $this->assertTrue(is_object($result));
        $this->assertEquals(34.5, $result->xmin);
    }

    public function testFeatureExtentsValidatesLayerName()
    {
        $service = $this->createService([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid layer name [invalid]');
        $service->featureExtents('invalid', '1');
    }

    // ---------------------------------------------------------------
    // Pagination param in search requests
    // ---------------------------------------------------------------

    public function testPaginationParamSentInSearchRequests()
    {
        $body = json_encode(['Addresses' => []]);
        $history = [];

        $service = $this->createService([new Response(200, [], $body)], $history);
        $service->freeTextSearch('test', 3);

        $params = $this->getQueryParams($history);
        $this->assertEquals('3', $params['page']);
    }
}
