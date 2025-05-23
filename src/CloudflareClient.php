<?php

namespace CloudflareQueue;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class CloudflareClient
{
    protected PendingRequest $client;

    public function __construct(private readonly array $config)
    {
        $this->client = $this->createClient();
    }

    public function ack(string $leaseId): bool
    {
        $response = $this->client->post('/messages/ack', [
            'acks' => [
                [
                    'lease_id' => $leaseId,
                ],
            ],
        ]);

        return $response->json('success');
    }

    public function retry(string $leaseId, int $delay): bool
    {
        $response = $this->client->post('/messages/ack', [
            'retries' => [
                [
                    'lease_id' => $leaseId,
                    'delay_seconds' => $delay,
                ],
            ],
        ]);

        return $response->json('success');
    }

    public function clear($queue): bool
    {
        $response = $this->client->post('/purge', [
            'delete_messages_permanently' => true,
        ]);

        return $response->json('success');
    }

    public function send($payload, $queue = null, array $options = []): bool
    {
        $response = $this->client->post('messages', [
            'body' => $payload,
            'content_type' => 'text',
        ]);

        return $response->json('success');
    }

    public function get($queue = null): array
    {
        $response = $this->client->post('messages/pull', [
            'batch_size' => 1,
            'visibility_timeout' => 10000,
        ]);

        return $response->json();
    }

    protected function createClient()
    {
        return Http::asJson()
            ->asJson()
            ->withUserAgent('laravel-cloudflare-queues')
            ->baseUrl("https://api.cloudflare.com/client/v4/accounts/{$this->config['account_id']}/queues/{$this->config['queue_id']}")
            ->withToken($this->config['api_token']);
    }
}
