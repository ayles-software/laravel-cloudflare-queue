<?php

namespace CloudflareQueue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

class CloudflareJob extends Job implements JobContract
{
    protected $queue;

    public function __construct(
        Container $container,
        protected CloudflareClient $client,
        protected array $job,
        $connectionName,
        $queue
    ) {
        $this->container = $container;
        $this->queue = $queue;
        $this->connectionName = $connectionName;
    }

    public function delete()
    {
        parent::delete();

        $this->client->ack($this->job['lease_id']);
    }

    public function release($delay = 0)
    {
        parent::release($delay);

        $this->client->retry($this->job['lease_id'], $delay);
    }

    public function getJobId()
    {
        return $this->job['id'];
    }

    public function getRawBody()
    {
        return $this->job['body'];
    }

    public function attempts()
    {
        return $this->job['attempts'];
    }
}
