<?php

namespace Naif\Saudiaddress\Tests\Feature;

use Naif\Saudiaddress\Facades\SaudiAddress;
use Naif\Saudiaddress\Tests\TestCase;

class FacadeTest extends TestCase
{
    public function testFacadeResolvesToSaudiAddressInstance()
    {
        $resolved = SaudiAddress::getFacadeRoot();
        $this->assertInstanceOf(\Naif\Saudiaddress\SaudiAddress::class, $resolved);
    }

    public function testFacadeAccessorIsCorrect()
    {
        // The facade should resolve 'saudi-address' from the container
        $fromFacade = SaudiAddress::getFacadeRoot();
        $fromContainer = $this->app->make('saudi-address');

        $this->assertSame($fromFacade, $fromContainer);
    }
}
