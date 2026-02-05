<?php

namespace Naif\Saudiaddress\Tests;

use Naif\Saudiaddress\SaudiAddressServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            SaudiAddressServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'SaudiAddress' => \Naif\Saudiaddress\Facades\SaudiAddress::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('saudiaddress.url', 'https://apina.address.gov.sa/NationalAddress/v3.1');
        $app['config']->set('saudiaddress.api_key', 'test-api-key');
        $app['config']->set('saudiaddress.format', 'JSON');
        $app['config']->set('saudiaddress.language', 'A');
        $app['config']->set('saudiaddress.timeout', 30);
    }
}
