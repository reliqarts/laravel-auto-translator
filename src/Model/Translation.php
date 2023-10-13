<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Model;

use ReliqArts\AutoTranslator\Contract\Translation as TranslationContract;

/**
 * @codeCoverageIgnore
 */
final readonly class Translation implements TranslationContract
{
    public function __construct(
        private string $value,
        private LanguageCode $languageCode,
        private string $sourceValue,
        private ?LanguageCode $sourceLanguageCode
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLanguageCode(): LanguageCode
    {
        return $this->languageCode;
    }

    public function getSourceValue(): string
    {
        return $this->sourceValue;
    }

    public function getSourceLanguageCode(): ?LanguageCode
    {
        return $this->sourceLanguageCode;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
