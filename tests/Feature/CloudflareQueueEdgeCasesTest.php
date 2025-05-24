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


test('worker processes a single job correctly', function () {
    // Arrange
    // Create a single mock message with a real job class
    $jobData = ['id' => 1];
    $jobPayload = json_encode([
        'job' => \CloudflareQueue\Tests\Fixtures\TestJob::class.'@handle',
        'data' => $jobData
    ]);

    $message = [
        'id' => 'test-id-1',
        'body' => $jobPayload,
        'lease_id' => 'test-lease-id-1',
        'attempts' => 1
    ];

    // Mock the client to return our single message
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null, 5)
        ->andReturn(['result' => ['messages' => [$message]]]);

    // The job should be acknowledged when processed
    $client->shouldReceive('ack')
        ->once()
        ->with('test-lease-id-1');

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
    $worker->runNextJob('cloudflare', '', $options);

    // Assert is handled by the mock expectations
});

test('queue size method returns correct count', function () {
    // Arrange
    // Mock the client to return a specific message backlog count
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null, 1)
        ->andReturn(['result' => ['message_backlog_count' => 42]]);

    // Create the queue with our mocked client
    $queue = new CloudflareQueue($client);

    // Create a container mock
    $container = Mockery::mock('Illuminate\Container\Container');
    $container->shouldReceive('bound')->andReturn(true);
    $container->shouldReceive('offsetGet')->andReturn(null);

    $queue->setContainer($container);

    // Act
    $size = $queue->size();

    // Assert
    expect($size)->toBe(42);
});

test('queue size method handles missing backlog count', function () {
    // Arrange
    // Mock the client to return a response without a message_backlog_count
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null, 1)
        ->andReturn(['result' => []]);

    // Create the queue with our mocked client
    $queue = new CloudflareQueue($client);

    // Create a container mock
    $container = Mockery::mock('Illuminate\Container\Container');
    $container->shouldReceive('bound')->andReturn(true);
    $container->shouldReceive('offsetGet')->andReturn(null);

    $queue->setContainer($container);

    // Act
    $size = $queue->size();

    // Assert
    expect($size)->toBe(0);
});

test('job release method calls client retry', function () {
    // Arrange
    // Mock the client
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('retry')
        ->once()
        ->with('test-lease-id', 60);

    // Create a container mock
    $container = Mockery::mock('Illuminate\Container\Container');
    $container->shouldReceive('bound')->andReturn(true);
    $container->shouldReceive('offsetGet')->andReturn(null);

    // Create a job with our mocked client
    $job = new CloudflareJob(
        $container,
        $client,
        [
            'id' => 'test-id',
            'body' => 'test-body',
            'lease_id' => 'test-lease-id',
            'attempts' => 1
        ],
        'cloudflare',
        null
    );

    // Act
    $job->release(60);

    // Assert is handled by the mock expectations
});

afterEach(function () {
    Mockery::close();
});
