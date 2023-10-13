<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Unit\Model;

use InvalidArgumentException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use ReliqArts\AutoTranslator\Model\LanguageCode;

/**
 * @coversDefaultClass \ReliqArts\AutoTranslator\Model\LanguageCode
 */
final class LanguageCodeTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testFrom(): void
    {
        $code = 'en';
        $it = LanguageCode::from($code);

        self::assertSame($code, (string) $it);
    }

    public function testFromWhenCodeIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LanguageCode::from('bad-value');
    }
}
