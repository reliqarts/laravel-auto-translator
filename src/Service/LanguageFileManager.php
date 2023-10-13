<?php

/** @noinspection PhpClassCanBeReadonlyInspection */

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Service;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use JsonException;
use ReliqArts\AutoTranslator\Model\LanguageCode;
use ReliqArts\Contract\Filesystem;

class LanguageFileManager
{
    public function __construct(private readonly Application $app, private readonly Filesystem $filesystem)
    {
    }

    /**
     * @throws FileNotFoundException
     * @throws JsonException
     */
    public function read(LanguageCode $language): array
    {
        $filePath = $this->getLanguageFilePath($language);
        $contents = $this->filesystem->get($filePath);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public function write(LanguageCode $language, array $translationMappings): bool
    {
        $filePath = $this->getLanguageFilePath($language);
        $contents = json_encode($translationMappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $this->filesystem->replace($filePath, $contents) !== false;
    }

    private function getLanguageFilePath(LanguageCode $language): string
    {
        return $this->app->langPath(sprintf('%s.json', $language));
    }
}
