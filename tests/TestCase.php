<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Tests;

use Illuminate\Database\Migrations\Migration;
use Orchestra\Testbench\TestCase as Orchestra;
use WeBRTeu\Payments\PaymentsServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [PaymentsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        /** @var Migration $migration */
        $migration = require __DIR__.'/../database/migrations/create_payments_tables.php.stub';
        $migration->up();

        $this->beforeApplicationDestroyed(function () use ($migration): void {
            $migration->down();
        });
    }
}
