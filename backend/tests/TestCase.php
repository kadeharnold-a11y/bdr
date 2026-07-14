<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // The auth manager caches the resolved guard/user across requests made
    // within a single test, so a request authenticated as a citizen would
    // "leak" into a following request that presents a staff bearer token.
    // Forget guards before every simulated request so each one authenticates
    // fresh from its own Authorization header, like real HTTP would.
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
