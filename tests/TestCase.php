<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // CreatesApplication refuses to boot unless the suite is pointed at the
    // in-memory SQLite database, so no test can reach real data.
    use CreatesApplication;
}
