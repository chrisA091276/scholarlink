<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');
        if (! ($connection === 'sqlite' && $database === ':memory:')
            && ! ($connection === 'mysql' && $database === 'scholarlink_qa_20261027_test')) {
            throw new \RuntimeException('Refusing tests outside the isolated SQLite or dedicated MySQL test database. Clear cached configuration first.');
        }
        return $app;
    }
}
