<?php

namespace CloudflareQueue;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving('queue', function ($queue) {
            $queue->addConnector('cloudflare', function () {
                return new CloudflareConnector;
            });
        });

        $this->registerCloudflareFailedJobProvider();
    }

    protected function registerCloudflareFailedJobProvider(): void
    {
        $this->app->extend('queue.failer', function ($failer, $app) {
            $config = $app['config']['queue.failed'];

            if (isset($config['driver']) && $config['driver'] === 'cloudflare') {
                return new CloudflareFailedJobProvider(
                    $app['db'],
                    $config['database'] ?? $app['config']['database.default'],
                    $config['table'] ?? 'cloudflare_failed_jobs'
                );
            }

            return $failer;
        });
    }
}
