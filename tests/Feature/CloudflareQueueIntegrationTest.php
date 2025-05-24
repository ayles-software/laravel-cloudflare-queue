<?php

namespace CloudflareQueue\Tests\Feature;

use CloudflareQueue\CloudflareClient;
use CloudflareQueue\CloudflareJob;
use CloudflareQueue\CloudflareQueue;
use CloudflareQueue\CloudflareWorker;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\WorkerOptions;
use Mockery;

test('full workflow with batch processing', function () {
    // Arrange
    // Create mock messages that will be returned by the client
    $messages = [
        [
            'id' => 'test-id-1',
            'body' => json_encode([
                'job' => \CloudflareQueue\Tests\Fixtures\TestJob::class.'@handle',
                'data' => ['id' => 1]
            ]),
            'lease_id' => 'test-lease-id-1',
            'attempts' => 1
        ],
        [
            'id' => 'test-id-2',
            'body' => json_encode([
                'job' => \CloudflareQueue\Tests\Fixtures\TestJob::class.'@handle',
                'data' => ['id' => 2]
            ]),
            'lease_id' => 'test-lease-id-2',
            'attempts' => 1
        ],
        [
            'id' => 'test-id-3',
            'body' => json_encode([
                'job' => \CloudflareQueue\Tests\Fixtures\TestJob::class.'@handle',
                'data' => ['id' => 3]
            ]),
            'lease_id' => 'test-lease-id-3',
            'attempts' => 1
        ]
    ];

    // Mock the client to return our test messages
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null, 5) // Using the batch size from our TestCase
        ->andReturn(['result' => ['messages' => $messages]]);

    // Track which jobs were processed
    $processedJobs = [];

    // Mock the ack method to track processed jobs
    $client->shouldReceive('ack')
        ->times(3)
        ->withArgs(function ($leaseId) use (&$processedJobs, $messages) {
            // Find which message this lease ID belongs to
            foreach ($messages as $message) {
                if ($message['lease_id'] === $leaseId) {
                    $data = json_decode($message['body'], true);
                    $processedJobs[] = $data['data']['id'];
                    return true;
                }
            }
            return false;
        });

    // Create the queue with our mocked client
    $queue = new CloudflareQueue($client, ['batch_size' => 5]);

    // Create a container mock that can resolve our TestJob class
    $container = Mockery::mock('Illuminate\Container\Container');
    $container->shouldReceive('bound')->andReturn(true);
    $container->shouldReceive('offsetGet')->andReturn(null);
    $container->shouldReceive('make')
        ->with(\CloudflareQueue\Tests\Fixtures\TestJob::class)
        ->andReturn(new \CloudflareQueue\Tests\Fixtures\TestJob());

    $queue->setContainer($container);

    // Create the necessary dependencies for the worker
    $manager = Mockery::mock(QueueManager::class);
    $manager->shouldReceive('connection')
        ->once()
        ->with('cloudflare')
        ->andReturn($queue);

    $events = Mockery::mock(Dispatcher::class);
    $events->shouldReceive('dispatch')->zeroOrMoreTimes();

    $handler = Mockery::mock(ExceptionHandler::class);
    $handler->shouldReceive('report')->zeroOrMoreTimes();

    // Create the isDownForMaintenance and resetScope callables
    $isDownForMaintenance = function () {
        return false;
    };

    $resetScope = function () {
        // No-op for testing
    };

    // Create the worker
    $worker = new CloudflareWorker(
        $manager,
        $events,
        $handler,
        $isDownForMaintenance,
        $resetScope
    );

    // Create worker options
    $options = new WorkerOptions(0, 0, 0, 0, 0, 0);

    // Act
    // Process the next batch of jobs
    $worker->runNextJob('cloudflare', '', $options);

    // Assert
    // Verify that all jobs were processed
    expect($processedJobs)->toHaveCount(3);
    expect($processedJobs)->toContain(1);
    expect($processedJobs)->toContain(2);
    expect($processedJobs)->toContain(3);
});

test('batch size from config is used', function () {
    // Arrange
    // Mock the client to verify the batch size
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null, 10) // Default batch size is 10
        ->andReturn(['result' => ['messages' => []]]);

    // Create the queue with our mocked client
    $container = Mockery::mock('Illuminate\Container\Container');
    $container->shouldReceive('bound')->andReturn(true);
    $container->shouldReceive('offsetGet')->andReturn(null);

    $queue = new CloudflareQueue($client);
    $queue->setContainer($container);

    // Act
    $queue->pop();

    // Assert is handled by the mock expectations
});

test('custom batch size overrides config', function () {
    // Arrange
    // Mock the client to verify the batch size
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null, 8) // Custom batch size
        ->andReturn(['result' => ['messages' => []]]);

    // Create the queue with our mocked client and custom batch size
    $container = Mockery::mock('Illuminate\Container\Container');
    $container->shouldReceive('bound')->andReturn(true);
    $container->shouldReceive('offsetGet')->andReturn(null);

    $queue = new CloudflareQueue($client, ['batch_size' => 8]);
    $queue->setContainer($container);

    // Act
    $queue->pop();

    // Assert is handled by the mock expectations
});

afterEach(function () {
    Mockery::close();
});
