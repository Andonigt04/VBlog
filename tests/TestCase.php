<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Vite manifest does not exist in CI (assets not built for unit/feature tests).
        // withoutVite() makes @vite directives return empty strings instead of throwing.
        $this->withoutVite();
    }
}
