<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Integration\Http\Middleware;

use Illuminate\Support\Facades\Route;
use ReliqArts\AutoTranslator\Http\Middleware\LanguageDetector;
use ReliqArts\AutoTranslator\Tests\Integration\TestCase;
use RuntimeException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

final class LanguageDetectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(LanguageDetector::class)
            ->any('detect-lang', fn () => 'OK');
    }

    /**
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function testHandle(): void
    {
        $this->withSession(['lang' => 'fr'])
            ->get('detect-lang');

        self::assertSame('fr', $this->app->getLocale());
    }
}
