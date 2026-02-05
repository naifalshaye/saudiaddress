<?php

namespace Naif\Saudiaddress\Tests\Feature;

use Naif\Saudiaddress\SaudiAddress;
use Naif\Saudiaddress\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function testConfigIsLoaded()
    {
        $this->assertNotNull(config('saudiaddress.url'));
        $this->assertNotNull(config('saudiaddress.api_key'));
    }

    public function testConfigHasExpectedKeys()
    {
        $config = config('saudiaddress');

        $this->assertArrayHasKey('url', $config);
        $this->assertArrayHasKey('api_key', $config);
        $this->assertArrayHasKey('format', $config);
        $this->assertArrayHasKey('language', $config);
        $this->assertArrayHasKey('timeout', $config);
    }

    public function testConfigValuesAreSetFromEnvironment()
    {
        $this->assertEquals(
            'https://apina.address.gov.sa/NationalAddress/v3.1',
            config('saudiaddress.url')
        );
        $this->assertEquals('test-api-key', config('saudiaddress.api_key'));
    }

    public function testServiceIsRegisteredInContainer()
    {
        $service = $this->app->make('saudi-address');
        $this->assertInstanceOf(SaudiAddress::class, $service);
    }

    public function testServiceIsSingleton()
    {
        $first = $this->app->make('saudi-address');
        $second = $this->app->make('saudi-address');

        $this->assertSame($first, $second);
    }

    public function testConfigIsPublishable()
    {
        $publishable = \Illuminate\Support\ServiceProvider::$publishes;

        $found = false;
        foreach ($publishable as $provider => $paths) {
            foreach ($paths as $source => $destination) {
                if (strpos($source, 'saudiaddress.php') !== false) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found, 'The saudiaddress config should be publishable.');
    }
}
