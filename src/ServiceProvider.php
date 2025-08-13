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
    }
}
