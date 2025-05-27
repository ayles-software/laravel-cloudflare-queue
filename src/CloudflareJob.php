<?php

namespace CloudflareQueue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\Jobs\JobName;

class CloudflareJob extends Job implements JobContract
{
    protected $queue;

    public function __construct(
        Container $container,
        protected CloudflareClient $client,
        protected array $job,
        protected array $config,
        $connectionName,
        $queue,
    ) {
        $this->container = $container;
        $this->queue = $queue;
        $this->connectionName = $connectionName;
    }

    public function delete(): void
    {
        parent::delete();

        $this->client->ack($this->job['lease_id']);
    }

    public function release($delay = 0): void
    {
        parent::release($delay);

        $this->client->retry($this->job['lease_id'], $delay);
    }

    public function getJobId(): string
    {
        return $this->job['id'];
    }

    public function getRawBody(): string
    {
        return $this->job['body'];
    }

    public function attempts(): int
    {
        return $this->job['attempts'];
    }

    public function fire()
    {
        $payload = $this->payload();

        [$class, $method] = JobName::parse($payload['job']);

        $this->instance = new $class($payload['data'] ?? []);
        $this->instance->{$method}();
    }

    public function payload(): array
    {
        $payload = parent::payload();

        if (! $this->config['raw']) {
            return $payload;
        }

        return [
            'job' => $this->config['handler'].'@handle',
            'uuid' => $this->job['id'],
            'id' => $this->job['id'],
            'data' => $payload,
        ];
    }
}
