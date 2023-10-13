<?php

/** @noinspection PhpClassCanBeReadonlyInspection */

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Service;

use KKomelin\TranslatableStringExporter\Core\Exporter;
use ReliqArts\AutoTranslator\Model\LanguageCode;

class TranslatableStringExporter
{
    public function __construct(readonly private Exporter $exporter)
    {
    }

    public function export(LanguageCode ...$languageCodes): void
    {
        foreach ($languageCodes as $code) {
            $this->exporter->export((string) $code);
        }
    }
}
