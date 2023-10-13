<?php
/**
 * @noinspection SpellCheckingInspection
 */

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Integration;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ReliqArts\AutoTranslator\ServiceProvider;
use ReliqArts\ServiceProvider as ReliqArtsServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ReliqArtsServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF');
    }
}
