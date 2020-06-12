<?php

namespace Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setTestNow($now)
    {
        Carbon::setTestNow($now);
        CarbonImmutable::setTestNow($now);
    }
}
