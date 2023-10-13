<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Service\Translator;

use DeepL\DeepLException;
use DeepL\Translator as DeepL;
use InvalidArgumentException;
use ReliqArts\AutoTranslator\Contract\ConfigProvider;
use ReliqArts\AutoTranslator\Contract\Translation as TranslationContract;
use ReliqArts\AutoTranslator\Exception\TranslationFailedException;
use ReliqArts\AutoTranslator\Model\LanguageCode;
use ReliqArts\AutoTranslator\Model\Translation;
use ReliqArts\AutoTranslator\Service\Translator;

final class DeepLTranslator extends Translator
{
    private ?DeepL $translator = null;

    /**
     * @throws TranslationFailedException
     */
    public function translate(
        string $value,
        LanguageCode|string $targetLanguage,
        LanguageCode $sourceLanguage = null
    ): TranslationContract {
        try {
            $result = $this->getTranslator()
                ->translateText($value, (string) $sourceLanguage, (string) $targetLanguage);

            try {
                $sourceLang = LanguageCode::from($result->detectedSourceLang);
            } catch (InvalidArgumentException) {
                $sourceLang = $sourceLanguage;
            }

            return new Translation($result->text, $targetLanguage, $value, $sourceLang);
        } catch (DeepLException $e) {
            $message = sprintf('Translation failed in %s. Message: %s', get_class($this), $e->getMessage());

            $this->logger->error($message);

            throw new TranslationFailedException($message, $e->getCode(), $e);
        }
    }

    public function getSlug(): string
    {
        return 'deepl_translator';
    }

    /**
     * @throws DeepLException
     */
    private function getTranslator(): DeepL
    {
        if ($this->translator === null) {
            $this->translator = new DeepL($this->getConfig()[ConfigProvider::KEY_API_KEY]);
        }

        return $this->translator;
    }
}
