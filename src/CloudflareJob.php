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

    public function payload(): array
    {
        $payload = parent::payload();

        if (! $this->config['raw']) {
            return $payload;
        }

        return [
            'id' => $this->job['id'],
            'uuid' => $this->job['id'],
            'displayName' => $this->config['handler'],
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => $this->config['handler'],
                'command' => serialize(new ($this->config['handler'])($payload ?? [])),
            ],
        ];
    }
}
