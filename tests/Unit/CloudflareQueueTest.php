<?php

namespace CloudflareQueue\Tests\Unit;

use CloudflareQueue\CloudflareClient;
use CloudflareQueue\CloudflareJob;
use CloudflareQueue\CloudflareQueue;
use Illuminate\Container\Container;
use Mockery;
use Mockery\MockInterface;

test('pop returns null when no messages are available', function () {
    // Arrange
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null)
        ->andReturn(['result' => ['messages' => []]]);

    $queue = new CloudflareQueue($client, ['batch_size' => 5]);
    $queue->setContainer(new Container());

    // Act
    $result = $queue->pop();

    // Assert
    expect($result)->toBeNull();
});

test('pop returns a single job when one message is available', function () {
    // Arrange
    $message = [
        'id' => 'test-id',
        'body' => 'test-body',
        'lease_id' => 'test-lease-id',
        'attempts' => 1
    ];

    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null)
        ->andReturn(['result' => ['messages' => [$message]]]);

    $queue = new CloudflareQueue($client, ['batch_size' => 5]);
    $queue->setContainer(new Container());

    // Act
    $result = $queue->pop();

    // Assert
    expect($result)->toBeInstanceOf(CloudflareJob::class);
    expect($result->getJobId())->toBe('test-id');
    expect($result->getRawBody())->toBe('test-body');
});

test('pop returns only the first job when multiple messages are available', function () {
    // Arrange
    $messages = [
        [
            'id' => 'test-id-1',
            'body' => 'test-body-1',
            'lease_id' => 'test-lease-id-1',
            'attempts' => 1
        ],
        [
            'id' => 'test-id-2',
            'body' => 'test-body-2',
            'lease_id' => 'test-lease-id-2',
            'attempts' => 1
        ],
        [
            'id' => 'test-id-3',
            'body' => 'test-body-3',
            'lease_id' => 'test-lease-id-3',
            'attempts' => 1
        ]
    ];

    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null)
        ->andReturn(['result' => ['messages' => $messages]]);

    $queue = new CloudflareQueue($client, ['batch_size' => 5]);
    $queue->setContainer(new Container());

    // Act
    $result = $queue->pop();

    // Assert
    expect($result)->toBeInstanceOf(CloudflareJob::class);
    expect($result->getJobId())->toBe('test-id-1');
    expect($result->getRawBody())->toBe('test-body-1');
});

afterEach(function () {
    Mockery::close();
});
