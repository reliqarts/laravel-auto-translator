<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Service;

use Exception;
use Generator;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use InvalidArgumentException;
use JsonException;
use ReliqArts\AutoTranslator\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\AutoTranslator\Contract\Translator;
use ReliqArts\AutoTranslator\Exception\AutoTranslationFailedException;
use ReliqArts\AutoTranslator\Exception\TranslationFailedException;
use ReliqArts\AutoTranslator\Exception\TranslatorNotConfiguredException;
use ReliqArts\AutoTranslator\Model\LanguageCode;
use ReliqArts\AutoTranslator\Service;
use ReliqArts\Contract\Logger as LoggerContract;
use ReliqArts\Result;

final class Engine implements Service
{
    private const KEY_LANGUAGE = 'language';

    private const KEY_EXCEPTION_TRACE = 'exception_trace';

    private const KEY_NEW_TRANSLATIONS = 'new_translations';

    private const KEY_TRANSLATED_STRINGS_COUNT = 'translated_strings_count';

    private const KEY_TRANSLATIONS_NOT_REPLACED = 'not_replaced';

    private const VAR_PATTERN = '/:\w+/u';

    private const VAR_PSEUDO_PLACEHOLDER = '_-_';

    private ?Translator $translator = null;

    public function __construct(
        private readonly ConfigProviderContract $configProvider,
        private readonly LoggerContract $logger,
        private readonly TranslatableStringExporter $translatableStringExporter,
        private readonly TranslatorProvider $translatorProvider,
        private readonly LanguageFileManager $languageFileManager
    ) {
    }

    public function translate(LanguageCode|string $language, bool $replaceExisting = false): Result
    {
        try {
            $translator = $this->getTranslator();
            $baseLanguage = $this->configProvider->get(ConfigProviderContract::KEY_BASE_LANGUAGE);
            $sourceLanguage = $baseLanguage !== null ? LanguageCode::from($baseLanguage) : null;
            $language = LanguageCode::from($language);

            $this->translatableStringExporter->export($language);

            $originalTranslationsMap = $this->languageFileManager->read($language);
            $newTranslationsMap = [];
            $notReplaced = 0;

            foreach ($originalTranslationsMap as $key => $translatedValue) {
                try {
                    if (! $replaceExisting && $key !== $translatedValue) {
                        $notReplaced++;

                        continue;
                    }

                    // replace variables in key with special constant to create a translation-safe key
                    preg_match_all(self::VAR_PATTERN, $key, $matches, PREG_PATTERN_ORDER);
                    $vars = $matches[0];
                    $safeKey = preg_replace(
                        array_map(static fn (string $varPlaceholder) => "/$varPlaceholder/", $vars),
                        self::VAR_PSEUDO_PLACEHOLDER,
                        $key
                    );

                    $translation = $translator->translate($safeKey, $language, $sourceLanguage);

                    // replace placeholder in translated string with variables
                    $newTranslatedValue = $translation->getValue();
                    $varPlaceholderPattern = sprintf('/%s/', self::VAR_PSEUDO_PLACEHOLDER);
                    foreach ($vars as $var) {
                        $newTranslatedValue = preg_replace($varPlaceholderPattern, $var, $newTranslatedValue, 1);
                    }

                    $newTranslationsMap[$key] = $newTranslatedValue;
                } catch (TranslationFailedException $e) {
                    $this->logger->error(sprintf('Translation failed for key: `%s` - %s', $key, $e->getMessage()));
                }
            }

            $this->languageFileManager->write($language, array_merge($originalTranslationsMap, $newTranslationsMap));

            return new Result(true, extra: [
                self::KEY_NEW_TRANSLATIONS => $newTranslationsMap,
                self::KEY_TRANSLATIONS_NOT_REPLACED => $notReplaced,
            ]);
        } catch (FileNotFoundException|JsonException $e) {
            $errorMessage = sprintf('Failed to read/write `%s` language file. %s', $language, $e->getMessage());
            $context = [self::KEY_LANGUAGE => (string) $language, self::KEY_EXCEPTION_TRACE => $e->getTraceAsString()];

            $this->logger->error($errorMessage, $context);

            return new Result(false, $errorMessage, extra: $context);
        } catch (TranslatorNotConfiguredException|InvalidArgumentException $e) {
            $errorMessage = sprintf('A configuration error occurred. %s', $e->getMessage());
            $context = [self::KEY_EXCEPTION_TRACE => $e->getTraceAsString()];

            $this->logger->error($errorMessage, $context);

            return new Result(false, $errorMessage, extra: $context);
        }
    }

    /**
     * @throws AutoTranslationFailedException
     */
    public function translateMany(iterable $targetLanguages, bool $replaceExisting = false): Result
    {
        try {
            $targetLanguages = empty($targetLanguages) ? $this->getTargetLanguages() : $targetLanguages;
            $languagesAttempted = $languagesTranslated = $translatedStringsCount = $translationsNotReplaced = 0;
            $messages = [];

            foreach ($targetLanguages as $language) {
                $languagesAttempted++;
                $result = $this->translate($language, $replaceExisting);

                if ($result->isSuccess()) {
                    $languagesTranslated++;
                    $translatedStringsCount += count($result->getExtra()[self::KEY_NEW_TRANSLATIONS]);
                    $translationsNotReplaced += $result->getExtra()[self::KEY_TRANSLATIONS_NOT_REPLACED];

                    continue;
                }

                $messages[] = $result->getError();
            }

            $summary = sprintf('%d/%d languages were translated.', $languagesTranslated, $languagesAttempted);
            if ($translationsNotReplaced > 0) {
                $summary .= sprintf(' %d existing translations were not replaced.', $translationsNotReplaced);
            }

            if ($languagesAttempted !== $languagesTranslated) {
                return new Result(
                    false,
                    $summary.' See `messages` for further details.',
                    messages: $messages,
                    extra: [self::KEY_TRANSLATED_STRINGS_COUNT => $translatedStringsCount]
                );
            }

            // prepend summary to messages array
            array_unshift($messages, $summary);

            return new Result(
                true,
                messages: $messages,
                extra: [self::KEY_TRANSLATED_STRINGS_COUNT => $translatedStringsCount]
            );
        } catch (Exception $e) {
            $this->logger->error(sprintf('Exception occurred in auto translator engine. %s', $e->getMessage()));

            throw new AutoTranslationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function translateAll(bool $replaceExisting = false): Result
    {
        return $this->translateMany($this->getTargetLanguages(), $replaceExisting);
    }

    /**
     * @throws TranslatorNotConfiguredException
     */
    private function getTranslator(): Translator
    {
        try {
            if ($this->translator === null) {
                $this->translator = $this->translatorProvider->get();
            }

            return $this->translator;
        } catch (BindingResolutionException $e) {
            throw new TranslatorNotConfiguredException(
                sprintf('Failed to get translator for auto-translation. %s', $e->getMessage())
            );
        }
    }

    /**
     * @return Generator<LanguageCode>
     */
    private function getTargetLanguages(): Generator
    {
        foreach ($this->configProvider->get(ConfigProviderContract::KEY_TARGET_LANGUAGES) as $code) {
            try {
                $language = LanguageCode::from($code);

                yield $language;
            } catch (InvalidArgumentException $e) {
                $this->logger->warning(
                    sprintf('Configured language code `%s` is invalid. %s. Skipped.', $code, $e->getMessage())
                );
            }
        }
    }
}
