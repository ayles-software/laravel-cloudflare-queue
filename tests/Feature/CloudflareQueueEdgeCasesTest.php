<?php

namespace CloudflareQueue\Tests\Feature;

use CloudflareQueue\CloudflareClient;
use CloudflareQueue\CloudflareJob;
use CloudflareQueue\CloudflareQueue;
use Mockery;



test('queue size method returns correct count', function () {
    // Arrange
    // Mock the client to return a specific message backlog count
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null)
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
        ->with(null)
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
        [
            'raw_handler' => null,
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
