<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Integration;

use ReliqArts\AutoTranslator\Service;
use ReliqArts\AutoTranslator\Service\Engine;
use Throwable;

final class ServiceTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testServiceResolution(): void
    {
        self::assertInstanceOf(Engine::class, resolve(Service::class));
    }
}
