<?php

namespace CloudflareQueue;

use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;

class CloudflareQueue extends Queue implements QueueContract, ClearableQueue
{
    public function __construct(private readonly CloudflareClient $client)
    {
    }

    public function clear($queue)
    {
        return $this->client->clear($queue);
    }

    public function size($queue = null)
    {
        $response = $this->client->get($queue);

        return $response['result']['message_backlog_count'] ?? 0;
    }

    public function push($job, $data = '', $queue = null)
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

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->client->send($payload, $queue, $options);

        return json_decode($payload, true)['id'] ?? null;
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        // TODO: Implement later() method.
    }

//    public function bulk($jobs, $data = '', $queue = null)
//    {
//        // todo
//    }

    public function pop($queue = null)
    {
        $response = $this->client->get($queue);
        $messages = $response['result']['messages'] ?? [];

        if (count($messages) > 0) {
            return new CloudflareJob(
                $this->container,
                $this->client,
                $messages[0],
                $this->connectionName,
                $queue
            );
        }
    }
}
