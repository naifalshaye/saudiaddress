<?php

namespace Naif\Saudiaddress\Tests\Unit;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Naif\Saudiaddress\Exceptions\AddressNotFoundException;
use Naif\Saudiaddress\Exceptions\ApiRequestException;
use Naif\Saudiaddress\Exceptions\InvalidConfigurationException;
use Naif\Saudiaddress\Exceptions\InvalidResponseException;
use Naif\Saudiaddress\Exceptions\SaudiAddressException;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    // ---------------------------------------------------------------
    // Exception hierarchy
    // ---------------------------------------------------------------

    public function testSaudiAddressExceptionExtendsException()
    {
        $e = new SaudiAddressException('test');
        $this->assertInstanceOf(Exception::class, $e);
    }

    public function testInvalidConfigurationExceptionExtendsSaudiAddressException()
    {
        $e = InvalidConfigurationException::apiKeyNotSet();
        $this->assertInstanceOf(SaudiAddressException::class, $e);
    }

    public function testApiRequestExceptionExtendsSaudiAddressException()
    {
        $previous = new ConnectException('fail', new Request('GET', 'test'));
        $e = ApiRequestException::fromGuzzleException('/test', $previous);
        $this->assertInstanceOf(SaudiAddressException::class, $e);
    }

    public function testInvalidResponseExceptionExtendsSaudiAddressException()
    {
        $e = InvalidResponseException::invalidJson('/test');
        $this->assertInstanceOf(SaudiAddressException::class, $e);
    }

    public function testAddressNotFoundExceptionExtendsSaudiAddressException()
    {
        $e = AddressNotFoundException::forCoordinates(24.0, 46.0);
        $this->assertInstanceOf(SaudiAddressException::class, $e);
    }

    // ---------------------------------------------------------------
    // Factory method messages
    // ---------------------------------------------------------------

    public function testInvalidConfigurationApiKeyNotSetMessage()
    {
        $e = InvalidConfigurationException::apiKeyNotSet();
        $this->assertStringContainsString('API key is not set', $e->getMessage());
        $this->assertStringContainsString('SAUDI_ADDRESS_API_KEY', $e->getMessage());
    }

    public function testInvalidConfigurationApiUrlNotSetMessage()
    {
        $e = InvalidConfigurationException::apiUrlNotSet();
        $this->assertStringContainsString('API URL is not set', $e->getMessage());
        $this->assertStringContainsString('SAUDI_ADDRESS_API_URL', $e->getMessage());
    }

    public function testApiRequestExceptionFromGuzzleContainsEndpoint()
    {
        $previous = new ConnectException('Connection refused', new Request('GET', 'test'));
        $e = ApiRequestException::fromGuzzleException('/lookup/regions', $previous);

        $this->assertStringContainsString('/lookup/regions', $e->getMessage());
        $this->assertStringContainsString('Connection refused', $e->getMessage());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testApiRequestExceptionUnexpectedStatusCodeContainsCode()
    {
        $e = ApiRequestException::unexpectedStatusCode('/lookup/regions', 500);
        $this->assertStringContainsString('/lookup/regions', $e->getMessage());
        $this->assertStringContainsString('500', $e->getMessage());
    }

    public function testInvalidResponseExceptionInvalidJsonContainsEndpoint()
    {
        $e = InvalidResponseException::invalidJson('/lookup/regions');
        $this->assertStringContainsString('/lookup/regions', $e->getMessage());
        $this->assertStringContainsString('could not be decoded as JSON', $e->getMessage());
    }

    public function testInvalidResponseExceptionMissingFieldContainsFieldName()
    {
        $e = InvalidResponseException::missingField('/lookup/regions', 'Regions');
        $this->assertStringContainsString('/lookup/regions', $e->getMessage());
        $this->assertStringContainsString('Regions', $e->getMessage());
    }

    public function testAddressNotFoundExceptionContainsCoordinates()
    {
        $e = AddressNotFoundException::forCoordinates(24.774265, 46.738586);
        $this->assertStringContainsString('24.774265', $e->getMessage());
        $this->assertStringContainsString('46.738586', $e->getMessage());
    }

    public function testAddressNotFoundExceptionContainsShortAddress()
    {
        $e = AddressNotFoundException::forShortAddress('ABCD1234');
        $this->assertStringContainsString('ABCD1234', $e->getMessage());
        $this->assertStringContainsString('short address', $e->getMessage());
    }
}
