<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Contract;

use ReliqArts\AutoTranslator\Model\LanguageCode;
use Stringable;

interface Translation extends Stringable
{
    public function getValue(): string;

    public function getLanguageCode(): LanguageCode;

    public function getSourceValue(): string;

    public function getSourceLanguageCode(): ?LanguageCode;
}
