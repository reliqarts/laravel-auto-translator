<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Contract;

use ReliqArts\AutoTranslator\Exception\TranslationFailedException;
use ReliqArts\AutoTranslator\Model\LanguageCode;

interface Translator
{
    /**
     * @throws TranslationFailedException
     */
    public function translate(
        string $value,
        LanguageCode|string $targetLanguage,
        LanguageCode $sourceLanguage = null
    ): Translation;

    /**
     * Get the translator's slug. This is used as the key for the translator's specific configuration in the package
     * config file.
     */
    public function getSlug(): string;
}
