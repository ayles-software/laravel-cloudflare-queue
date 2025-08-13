<?php

namespace CloudflareQueue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use Ramsey\Uuid\Uuid;

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
        if (! $this->config['raw_handler']) {
            return $this->job['body'];
        }

        $payload = json_decode($this->job['body'], associative: true);
        $payload['uuid'] = Uuid::uuid4();

        return json_encode($payload);
    }

    public function attempts(): int
    {
        return $this->job['attempts'];
    }

    public function payload(): array
    {
        $payload = parent::payload();

        if (! $this->config['raw_handler']) {
            return $payload;
        }

        return [
            'id' => $this->job['id'],
            'uuid' => $this->job['id'],
            'displayName' => $this->config['raw_handler'],
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => $this->config['raw_handler'],
                'command' => serialize(new ($this->config['raw_handler'])($payload ?? [])),
            ],
        ];
    }
}
