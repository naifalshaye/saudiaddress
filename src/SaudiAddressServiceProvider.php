<?php

namespace Naif\Saudiaddress;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class SaudiAddressServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/saudiaddress.php' => config_path('saudiaddress.php'),
        ], 'saudiaddress-config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/saudiaddress.php',
            'saudiaddress'
        );

        $this->app->singleton('saudi-address', function ($app) {
            $config = $app['config']->get('saudiaddress');

            $client = new Client([
                'timeout' => $config['timeout'] ?? 30,
            ]);

            return new SaudiAddress($client, $config);
        });
    }
}
