<?php

namespace CloudflareQueue;

use Illuminate\Queue\Connectors\ConnectorInterface;

class CloudflareConnector implements ConnectorInterface
{
    public function connect(array $config): CloudflareQueue
    {
        return new CloudflareQueue(
            new CloudflareClient($config),
            $config
        );
    }
}
