<?php

namespace CloudflareQueue;

use Illuminate\Queue\Connectors\ConnectorInterface;

class CloudflareConnector implements ConnectorInterface
{
    public function connect(array $config)
    {
        return new CloudflareQueue(
            new CloudflareClient($config)
        );
    }
}
