<?php

namespace CloudflareQueue;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Client for interacting with the Cloudflare Queues API
 *
 * This class provides methods for sending, retrieving, acknowledging, and retrying messages
 * in a Cloudflare Queue. It includes support for bulk operations and error handling.
 */
class CloudflareClient
{
    protected PendingRequest $client;

    /**
     * Create a new CloudflareClient instance
     *
     * @param array $config Configuration array with the following keys:
     *                      - account_id: Cloudflare account ID
     *                      - queue_id: Cloudflare queue ID
     *                      - api_token: Cloudflare API token
     */
    public function __construct(private readonly array $config)
    {
        $this->client = $this->createClient();
    }

    /**
     * Acknowledge a message
     *
     * @param string $leaseId The lease ID of the message to acknowledge
     * @throws RuntimeException
     */
    public function ack(string $leaseId): bool
    {
        $response = $this->client->post('/messages/ack', [
            'acks' => [
                [
                    'lease_id' => $leaseId,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to acknowledge message: ' . $response->body());
        }

        return $response->json('success');
    }

    /**
     * Retry a message with a delay
     *
     * @param string $leaseId The lease ID of the message to retry
     * @param int $delay The delay in seconds before the message is available again
     * @throws RuntimeException|ConnectionException
     */
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

        if (! $response->successful()) {
            throw new RuntimeException('Failed to retry message: ' . $response->body());
        }

        return $response->json('success');
    }

    /**
     * Clear all messages from the queue
     *
     * @param mixed $queue Queue name (not used in this implementation)
     * @return bool
     * @throws RuntimeException|ConnectionException
     */
    public function clear($queue): bool
    {
        $response = $this->client->post('/purge', [
            'delete_messages_permanently' => true,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to clear queue: ' . $response->body());
        }

        return $response->json('success');
    }

    /**
     * Send a message to the queue
     *
     * @param mixed $payload The message payload
     * @param mixed $queue Queue name (not used in this implementation)
     * @param array $options Additional options for the message
     *                       - delay: The delay in seconds before the message is available
     * @throws RuntimeException
     */
    public function send($payload, $queue = null, array $options = []): bool
    {
        $data = [
            'body' => $payload,
            'content_type' => 'text',
        ];

        if (isset($options['delay']) && $options['delay'] > 0) {
            $data['delay_seconds'] = $options['delay'];
        }

        $response = $this->client->post('messages', $data);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to send message: ' . $response->body());
        }

        return $response->json('success');
    }

    /**
     * Send multiple messages to the queue in a single API call
     *
     * @param array $payloads Array of message payloads
     * @param mixed $queue Queue name (not used in this implementation)
     * @param array $options Additional options for the messages
     *                       - delay: The delay in seconds before the messages are available
     * @throws RuntimeException|ConnectionException
     */
    public function bulkSend(array $payloads, $queue = null, array $options = []): bool
    {
        if (empty($payloads)) {
            return true;
        }

        $messages = [];

        foreach ($payloads as $payload) {
            $message = [
                'body' => $payload,
                'content_type' => 'text',
            ];

            if (isset($options['delay']) && $options['delay'] > 0) {
                $message['delay_seconds'] = $options['delay'];
            }

            $messages[] = $message;
        }

        $response = $this->client->post('messages/bulk', [
            'messages' => $messages,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to bulk send messages: ' . $response->body());
        }

        return $response->json('success');
    }

    /**
     * Get messages from the queue
     *
     * @param mixed $queue Queue name (not used in this implementation)
     * @param int $batchSize Number of messages to retrieve (default: 1)
     * @param int $visibilityTimeout Time in seconds that the message is hidden from other consumers (default: 10000)
     * @throws RuntimeException|ConnectionException
     */
    public function get($queue = null, $batchSize = 1, $visibilityTimeout = 10000): array
    {
        $response = $this->client->post('messages/pull', [
            'batch_size' => $batchSize,
            'visibility_timeout' => $visibilityTimeout,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to get messages: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Acknowledge multiple messages in a single API call
     *
     * @param array $leaseIds Array of lease IDs to acknowledge
     * @throws RuntimeException|ConnectionException
     */
    public function bulkAck(array $leaseIds): bool
    {
        if (empty($leaseIds)) {
            return true;
        }

        $acks = [];

        foreach ($leaseIds as $leaseId) {
            $acks[] = ['lease_id' => $leaseId];
        }

        $response = $this->client->post('/messages/ack', [
            'acks' => $acks,
        ]);

        if ( !$response->successful()) {
            throw new RuntimeException('Failed to bulk acknowledge messages: ' . $response->body());
        }

        return $response->json('success');
    }

    /**
     * Retry multiple messages in a single API call
     *
     * @param array $retries Array of arrays with lease_id and delay_seconds
     * @throws RuntimeException|ConnectionException|InvalidArgumentException
     */
    public function bulkRetry(array $retries): bool
    {
        if (empty($retries)) {
            return true;
        }

        $formattedRetries = [];

        foreach ($retries as $retry) {
            if (!isset($retry['lease_id'])) {
                throw new InvalidArgumentException('Each retry must have a lease_id');
            }

            $formattedRetry = ['lease_id' => $retry['lease_id']];

            if (isset($retry['delay_seconds'])) {
                $formattedRetry['delay_seconds'] = $retry['delay_seconds'];
            }

            $formattedRetries[] = $formattedRetry;
        }

        $response = $this->client->post('/messages/ack', [
            'retries' => $formattedRetries,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to bulk retry messages: ' . $response->body());
        }

        return $response->json('success');
    }

    /**
     * Create and configure the HTTP client for Cloudflare API requests
     */
    protected function createClient(): PendingRequest
    {
        return Http::asJson()
            ->withUserAgent('laravel-cloudflare-queues')
            ->baseUrl("https://api.cloudflare.com/client/v4/accounts/{$this->config['account_id']}/queues/{$this->config['queue_id']}")
            ->withToken($this->config['api_token'])
            ->retry(3, 100);
    }
}
