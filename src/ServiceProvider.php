<?php

namespace CloudflareQueue;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Worker;
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

        // Extend the queue worker with our custom implementation
        $this->app->extend('queue.worker', function (Worker $worker, $app) {
            return new CloudflareWorker(
                $worker->getManager(),
                $app['events'],
                $app[ExceptionHandler::class],
                function () use ($app) {
                    return $app->isDownForMaintenance();
                },
                function () use ($app) {
                    if (method_exists($app['log'], 'flushSharedContext')) {
                        $app['log']->flushSharedContext();
                    }

                    if (method_exists($app['log'], 'withoutContext')) {
                        $app['log']->withoutContext();
                    }

                    if (method_exists($app['db'], 'getConnections')) {
                        foreach ($app['db']->getConnections() as $connection) {
                            $connection->resetTotalQueryDuration();
                            $connection->allowQueryDurationHandlersToRunAgain();
                        }
                    }

                    $app->forgetScopedInstances();
                }
            );
        });
    }
}
