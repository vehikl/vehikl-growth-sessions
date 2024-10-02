<?php

namespace Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use stdClass;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    public function setTestNowToASafeWednesday(): void
    {
        $this->setTestNow('2020-01-15');
    }

    public function setTestNow($now)
    {
        Carbon::setTestNow($now);
        CarbonImmutable::setTestNow($now);
    }

    public function loadFixture(string $filename): string
    {
        return file_get_contents(base_path("tests/fixtures/$filename"));
    }

    /** @return null|array|stdClass */
    public function loadJsonFixture(string $filename, bool $assoc = false)
    {
        return json_decode($this->loadFixture("$filename.json"), $assoc);
    }
}
