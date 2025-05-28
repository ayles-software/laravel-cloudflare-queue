<?php

namespace CloudflareQueue;

use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;

class CloudflareQueue extends Queue implements QueueContract, ClearableQueue
{
    protected bool $currentCountSet = false;
    protected int $currentCount = 0;

    public function __construct(
        private readonly CloudflareClient $client,
        private readonly array $config = [],
    ) {
        //
    }

    public function clear($queue): bool
    {
        return $this->client->clear($queue);
    }

    public function size($queue = null): int
    {
        if ($this->currentCountSet) {
            return $this->currentCount;
        }

        $response = $this->client->get($queue);

        return $response['result']['message_backlog_count'] ?? 0;
    }

    public function push($job, $data = '', $queue = null): mixed
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue, $data),
            $queue,
            null,
            function ($payload, $queue) {
                return $this->pushRaw($payload, $queue);
            }
        );
    }

    public function pushRaw($payload, $queue = null, array $options = []): ?string
    {
        $this->client->send($payload, $queue, $options);

        return json_decode($payload, true)['id'] ?? null;
    }

    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue, $data, $delay),
            $queue,
            $delay,
            function ($payload, $queue, $delay) {
                return $this->pushRaw($payload, $queue, ['delay' => $this->secondsUntil($delay)]);
            }
        );
    }

    public function bulk($jobs, $data = '', $queue = null): void
    {
        if (empty($jobs)) {
            return;
        }

        $payloads = [];

        foreach ($jobs as $job) {
            $payloads[] = $this->createPayload($job, $queue, $data);
        }

        $this->client->bulkSend($payloads, $queue);
    }

    public function pop($queue = null): array|CloudflareJob|null
    {
        $response = $this->client->get($queue);

        $this->currentCount = $response['result']['message_backlog_count'] ?? 0;

        if (! $this->currentCountSet) {
            $this->currentCountSet = true;
        }

        if (! isset($response['result']['messages'][0])) {
            return null;
        }

        return new CloudflareJob(
            $this->container,
            $this->client,
            $response['result']['messages'][0],
            [
                'raw' => $this->config['raw'] ?? false,
                'handler' => $this->config['handler'] ?? null,
            ],
            $this->connectionName,
            $queue
        );
    }
}
