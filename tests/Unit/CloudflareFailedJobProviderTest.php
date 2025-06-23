<?php

use CloudflareQueue\CloudflareFailedJobProvider;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    $this->tempFile = tempnam(sys_get_temp_dir(), 'cloudflare_failed_jobs_test_');
    $this->provider = new CloudflareFailedJobProvider($this->tempFile);
});

afterEach(function () {
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
});

test('it can log a failed job', function () {
    $connection = 'cloudflare';
    $queue = 'default';
    $payload = json_encode(['uuid' => 'test-uuid', 'data' => 'test-data']);
    $exception = new Exception('Test exception');

    $id = $this->provider->log($connection, $queue, $payload, $exception);

    expect($id)->toBe('test-uuid');

    $jobs = $this->provider->all();
    expect($jobs)->toHaveCount(1);
    expect($jobs[0]->id)->toBe('test-uuid');
    expect($jobs[0]->connection)->toBe($connection);
    expect($jobs[0]->queue)->toBe($queue);
    expect($jobs[0]->payload)->toBe($payload);
    expect($jobs[0]->exception)->toContain('Test exception');
});

test('it can find a failed job', function () {
    $connection = 'cloudflare';
    $queue = 'default';
    $payload = json_encode(['uuid' => 'test-uuid', 'data' => 'test-data']);
    $exception = new Exception('Test exception');

    $this->provider->log($connection, $queue, $payload, $exception);

    $job = $this->provider->find('test-uuid');

    expect($job)->not->toBeNull();
    expect($job->id)->toBe('test-uuid');
    expect($job->connection)->toBe($connection);
    expect($job->queue)->toBe($queue);
    expect($job->payload)->toBe($payload);
    expect($job->exception)->toContain('Test exception');
});

test('it can forget a failed job', function () {
    $connection = 'cloudflare';
    $queue = 'default';
    $payload = json_encode(['uuid' => 'test-uuid', 'data' => 'test-data']);
    $exception = new Exception('Test exception');

    $this->provider->log($connection, $queue, $payload, $exception);

    expect($this->provider->all())->toHaveCount(1);

    $result = $this->provider->forget('test-uuid');

    expect($result)->toBeTrue();
    expect($this->provider->all())->toHaveCount(0);
});

test('it can flush all failed jobs', function () {
    $connection = 'cloudflare';
    $queue = 'default';
    $payload = json_encode(['uuid' => 'test-uuid', 'data' => 'test-data']);
    $exception = new Exception('Test exception');

    $this->provider->log($connection, $queue, $payload, $exception);

    expect($this->provider->all())->toHaveCount(1);

    $this->provider->flush();

    expect($this->provider->all())->toHaveCount(0);
});

test('it can count failed jobs', function () {
    $connection = 'cloudflare';
    $queue = 'default';
    $payload = json_encode(['uuid' => 'test-uuid', 'data' => 'test-data']);
    $exception = new Exception('Test exception');

    $this->provider->log($connection, $queue, $payload, $exception);

    expect($this->provider->count())->toBe(1);
    expect($this->provider->count($connection))->toBe(1);
    expect($this->provider->count(null, $queue))->toBe(1);
    expect($this->provider->count($connection, $queue))->toBe(1);
    expect($this->provider->count('other-connection'))->toBe(0);
    expect($this->provider->count(null, 'other-queue'))->toBe(0);
});

test('it can prune old failed jobs', function () {
    $connection = 'cloudflare';
    $queue = 'default';
    $payload = json_encode(['uuid' => 'test-uuid', 'data' => 'test-data']);
    $exception = new Exception('Test exception');

    $this->provider->log($connection, $queue, $payload, $exception);

    expect($this->provider->all())->toHaveCount(1);

    // Prune jobs older than now (should remove all)
    $pruned = $this->provider->prune(Date::now()->addMinute());

    expect($pruned)->toBe(1);
    expect($this->provider->all())->toHaveCount(0);
});
