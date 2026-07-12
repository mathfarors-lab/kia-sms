<?php

namespace Tests;

use App\Support\BranchContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // BranchContext is static/process-wide; PHPUnit reuses one process
        // across test classes, so without this a lingering context from one
        // test would silently leak into the next.
        BranchContext::clear();
    }

    protected function tearDown(): void
    {
        BranchContext::clear();
        parent::tearDown();
    }
}
