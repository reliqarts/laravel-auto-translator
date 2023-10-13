<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator;

use ReliqArts\AutoTranslator\Exception\AutoTranslationFailedException;
use ReliqArts\AutoTranslator\Model\LanguageCode;
use ReliqArts\Result;

interface Service
{
    public function translate(LanguageCode|string $language, bool $replaceExisting = false): Result;

    /**
     * @throws AutoTranslationFailedException
     */
    public function translateAll(bool $replaceExisting = false): Result;

    /**
     * @throws AutoTranslationFailedException
     */
    public function translateMany(iterable $targetLanguages, bool $replaceExisting = false): Result;
}
