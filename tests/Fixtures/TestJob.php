<?php

namespace CloudflareQueue\Tests\Fixtures;

use Illuminate\Contracts\Queue\Job;

class TestJob
{
    /**
     * Create a new job instance.
     */
    public function __construct(public array $data = [])
    {
    }

    /**
     * Execute the job.
     */
    public function handle(Job $job, array $data)
    {
        // Process the job
        // In a real job, this would do something useful
        // For testing, we'll just delete the job from the queue
        $job->delete();
    }
}
