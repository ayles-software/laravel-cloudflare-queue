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
                    $config['path'] ?? $app->storagePath('framework/cache/cloudflare-failed-jobs.json'),
                    $config['limit'] ?? 100,
                    fn () => $app['cache']->store('file')
                );
            }

            return $failer;
        });
    }
}
