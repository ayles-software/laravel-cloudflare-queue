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
        ->with(null, 5)
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
        ->with(null, 5)
        ->andReturn(['result' => ['messages' => [$message]]]);

    $queue = new CloudflareQueue($client, ['batch_size' => 5]);
    $queue->setContainer(new Container());

    // Act
    $result = $queue->pop();

    // Assert
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(CloudflareJob::class);
    expect($result[0]->getJobId())->toBe('test-id');
    expect($result[0]->getRawBody())->toBe('test-body');
});

test('pop returns multiple jobs when multiple messages are available', function () {
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
        ->with(null, 5)
        ->andReturn(['result' => ['messages' => $messages]]);

    $queue = new CloudflareQueue($client, ['batch_size' => 5]);
    $queue->setContainer(new Container());

    // Act
    $result = $queue->pop();

    // Assert
    expect($result)->toBeArray();
    expect($result)->toHaveCount(3);

    expect($result[0])->toBeInstanceOf(CloudflareJob::class);
    expect($result[0]->getJobId())->toBe('test-id-1');
    expect($result[0]->getRawBody())->toBe('test-body-1');

    expect($result[1])->toBeInstanceOf(CloudflareJob::class);
    expect($result[1]->getJobId())->toBe('test-id-2');
    expect($result[1]->getRawBody())->toBe('test-body-2');

    expect($result[2])->toBeInstanceOf(CloudflareJob::class);
    expect($result[2]->getJobId())->toBe('test-id-3');
    expect($result[2]->getRawBody())->toBe('test-body-3');
});

test('batch size is configurable', function () {
    // Arrange
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null, 10)
        ->andReturn(['result' => ['messages' => []]]);

    $queue = new CloudflareQueue($client, ['batch_size' => 10]);
    $queue->setContainer(new Container());

    // Act
    $queue->pop();

    // No explicit assertion needed as the mock expectation verifies the batch size
});

test('default batch size is used when not configured', function () {
    // Arrange
    $client = Mockery::mock(CloudflareClient::class);
    $client->shouldReceive('get')
        ->once()
        ->with(null, 10) // Default batch size is 10
        ->andReturn(['result' => ['messages' => []]]);

    $queue = new CloudflareQueue($client); // No batch_size provided
    $queue->setContainer(new Container());

    // Act
    $queue->pop();

    // No explicit assertion needed as the mock expectation verifies the batch size
});

afterEach(function () {
    Mockery::close();
});
