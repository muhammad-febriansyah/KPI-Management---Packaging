<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Database names the suite must never touch. RefreshDatabase drops every table it
     * finds, so a misconfigured phpunit.xml or a stray DB_DATABASE would wipe real data.
     * Fail before the first query rather than after.
     */
    private const FORBIDDEN_DATABASES = ['staging'];

    protected function setUp(): void
    {
        parent::setUp();

        $database = config('database.connections.'.config('database.default').'.database');

        if (in_array($database, self::FORBIDDEN_DATABASES, true)) {
            throw new RuntimeException("Refusing to run the test suite against the \"{$database}\" database.");
        }
    }
}
