<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $compiled = sys_get_temp_dir().'/siclin-test-views-'.getmypid();
        if (! is_dir($compiled)) {
            mkdir($compiled, 0700, true);
        }
        $app['config']->set('view.compiled', $compiled);
        $app['config']->set('view.cache', false);
        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');
        if ($connection !== 'pgsql' || ! is_string($database) || ! str_ends_with($database, '_test')) {
            throw new \RuntimeException('Las pruebas requieren una base PostgreSQL explícita con sufijo _test.');
        }

        return $app;
    }
}
