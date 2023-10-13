<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Model;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Stringable;

/**
 * Language code representation via ISO-639
 *
 * @see https://cloud.google.com/translate/docs/languages
 * @see https://en.m.wikipedia.org/wiki/ISO_639
 */
readonly class LanguageCode implements Stringable
{
    private const MIN_LENGTH = 2;

    private const MAX_LENGTH = 5;

    private function __construct(private string $value)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function from(self|string $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        $valueLength = Str::length($value);
        if ($valueLength < self::MIN_LENGTH || $valueLength > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid language code `%s`, must be at least %d characters and no more that %d characters in length',
                    $value,
                    self::MIN_LENGTH,
                    self::MAX_LENGTH
                )
            );
        }

        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
