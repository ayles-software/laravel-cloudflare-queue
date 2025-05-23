<?php

namespace CloudflareQueue;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
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
