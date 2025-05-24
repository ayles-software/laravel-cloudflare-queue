<?php

namespace CloudflareQueue\Tests\Unit;

use CloudflareQueue\CloudflareJob;
use CloudflareQueue\CloudflareQueue;
use CloudflareQueue\CloudflareWorker;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\WorkerOptions;
use Mockery;

beforeEach(function() {
    $this->connection = Mockery::mock('Illuminate\Contracts\Queue\Queue');
    $this->connection->shouldReceive('getConnectionName')->andReturn('cloudflare');

    $this->manager = Mockery::mock(QueueManager::class);
    $this->manager->shouldReceive('connection')
        ->with('cloudflare')
        ->andReturn($this->connection);

    $this->events = Mockery::mock(Dispatcher::class);
    $this->events->shouldReceive('dispatch')->zeroOrMoreTimes();

    $this->handler = Mockery::mock(ExceptionHandler::class);
    $this->handler->shouldReceive('report')->zeroOrMoreTimes();

    $this->isDownForMaintenance = function() { return false; };
    $this->resetScope = function() { };
});

test('runNextJob processes multiple jobs when available', function () {
    // Arrange
    $job1 = Mockery::mock(CloudflareJob::class);
    $job2 = Mockery::mock(CloudflareJob::class);
    $job3 = Mockery::mock(CloudflareJob::class);

    $jobs = [$job1, $job2, $job3];

    $worker = Mockery::mock(CloudflareWorker::class, [
        $this->manager,
        $this->events,
        $this->handler,
        $this->isDownForMaintenance,
        $this->resetScope
    ])->makePartial();

    $worker->shouldAllowMockingProtectedMethods();

    $worker->shouldReceive('getNextJob')
        ->once()
        ->with($this->connection, 'default')
        ->andReturn($jobs);

    // Expect runJob to be called for each job
    $worker->shouldReceive('runJob')
        ->once()
        ->with($job1, 'cloudflare', Mockery::type(WorkerOptions::class));

    $worker->shouldReceive('runJob')
        ->once()
        ->with($job2, 'cloudflare', Mockery::type(WorkerOptions::class));

    $worker->shouldReceive('runJob')
        ->once()
        ->with($job3, 'cloudflare', Mockery::type(WorkerOptions::class));

    $options = new WorkerOptions(0, 0, 0, 0, 0, 0);

    // Act
    $worker->runNextJob('cloudflare', 'default', $options);

    // Assert is handled by the mock expectations
});

test('runNextJob processes a single job when only one is available', function () {
    // Arrange
    $job = Mockery::mock(CloudflareJob::class);

    $worker = Mockery::mock(CloudflareWorker::class, [
        $this->manager,
        $this->events,
        $this->handler,
        $this->isDownForMaintenance,
        $this->resetScope
    ])->makePartial();

    $worker->shouldAllowMockingProtectedMethods();

    $worker->shouldReceive('getNextJob')
        ->once()
        ->with($this->connection, 'default')
        ->andReturn($job);

    // Expect runJob to be called once with the job
    $worker->shouldReceive('runJob')
        ->once()
        ->with($job, 'cloudflare', Mockery::type(WorkerOptions::class));

    $options = new WorkerOptions(0, 0, 0, 0, 0, 0);

    // Act
    $worker->runNextJob('cloudflare', 'default', $options);

    // Assert is handled by the mock expectations
});


afterEach(function () {
    Mockery::close();
});
