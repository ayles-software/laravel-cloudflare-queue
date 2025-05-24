<?php

namespace CloudflareQueue\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * This is a simplified TestCase that doesn't depend on Orchestra\Testbench\TestCase.
     * It's used for running unit tests that don't require a full Laravel application.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Any setup needed for tests can be added here
    }
}
