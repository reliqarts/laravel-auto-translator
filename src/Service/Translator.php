<?php
/**
 * @noinspection PhpClassCanBeReadonlyInspection
 */

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Service;

use ReliqArts\AutoTranslator\Contract\ConfigProvider;
use ReliqArts\AutoTranslator\Contract\Translator as TranslatorContract;
use ReliqArts\Contract\Logger;

abstract class Translator implements TranslatorContract
{
    public function __construct(private readonly ConfigProvider $configProvider, protected readonly Logger $logger)
    {
    }

    final protected function getConfig(): array
    {
        return $this->configProvider->get(sprintf('translators.%s', $this->getSlug()), []);
    }
}
