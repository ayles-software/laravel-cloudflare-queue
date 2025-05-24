<?php

namespace CloudflareQueue;

use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;

class CloudflareWorker extends Worker
{
    /**
     * Process the next job on the queue.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param WorkerOptions $options
     * @return void
     */
    public function runNextJob($connectionName, $queue, WorkerOptions $options)
    {
        $jobs = $this->getNextJob(
            $this->manager->connection($connectionName), $queue
        );

        // If we're able to pull jobs off of the stack, we will process them and then return
        // from this method. If there are no jobs on the queue, we will "sleep" the worker
        // for the specified number of seconds, then keep processing jobs after sleep.
        if ($jobs) {
            // If $jobs is not an array, make it one for consistent handling
            if (! is_array($jobs)) {
                $jobs = [$jobs];
            }

            foreach ($jobs as $job) {
                $this->runJob($job, $connectionName, $options);
            }

            return;
        }

        $this->sleep($options->sleep);
    }
}
