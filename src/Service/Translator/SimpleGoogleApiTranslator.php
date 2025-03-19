<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Service\Translator;

use Exception;
use ReliqArts\AutoTranslator\Contract\ConfigProvider;
use ReliqArts\AutoTranslator\Contract\Translation as TranslationContract;
use ReliqArts\AutoTranslator\Exception\TranslationFailedException;
use ReliqArts\AutoTranslator\Model\LanguageCode;
use ReliqArts\AutoTranslator\Model\Translation;
use ReliqArts\AutoTranslator\Service\Translator;
use ReliqArts\Contract\Logger;
use Stichoza\GoogleTranslate\Exceptions\RateLimitException;
use Stichoza\GoogleTranslate\GoogleTranslate;

/**
 * Translates via Google's (public) API. Not advised for production usage, heavy rate limits are applied.
 */
final class SimpleGoogleApiTranslator extends Translator
{
    private const CONFIG_KEY_MAX_ATTEMPTS = 'max_attempts';

    private const CONFIG_KEY_WAIT_SECONDS = 'wait_seconds';

    private const DEFAULT_MAX_ATTEMPTS = 3;

    private const DEFAULT_WAIT_SECONDS = 5;

    public function __construct(
        ConfigProvider $configurationProvider,
        Logger $logger,
        private readonly GoogleTranslate $translator
    ) {
        parent::__construct($configurationProvider, $logger);
    }

    /**
     * @throws TranslationFailedException
     */
    public function translate(
        string $value,
        LanguageCode|string $targetLanguage,
        ?LanguageCode $sourceLanguage = null
    ): TranslationContract {
        try {
            $rawSourceLanguage = $sourceLanguage === null ? null : (string) $sourceLanguage;
            $this->translator->setTarget((string) $targetLanguage)
                ->setSource($rawSourceLanguage);

            $result = $this->attemptTranslation($value);
            $rawSourceLanguage = $rawSourceLanguage ?? $this->translator->getLastDetectedSource();

            return new Translation(
                $result,
                LanguageCode::from($targetLanguage),
                $value,
                $rawSourceLanguage === null ? null : LanguageCode::from($rawSourceLanguage)
            );
        } catch (Exception $e) {
            throw new TranslationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getSlug(): string
    {
        return 'simple_google_api_translator';
    }

    /**
     * @throws Exception
     */
    private function attemptTranslation(string $value, int $attemptNumber = 1): string
    {
        $config = $this->getConfig();
        $waitSeconds = $config[self::CONFIG_KEY_WAIT_SECONDS] ?? self::DEFAULT_WAIT_SECONDS;
        $maxAttempts = $config[self::CONFIG_KEY_MAX_ATTEMPTS] ?? self::DEFAULT_MAX_ATTEMPTS;

        try {
            return $this->translator->translate($value);
        } catch (RateLimitException $e) {
            if ($attemptNumber >= $maxAttempts) {
                $message = sprintf(
                    'Max attempts (%d) exhausted in %s after rate limit was hit. Will not retry. Message: %s',
                    $maxAttempts,
                    get_class($this),
                    $e->getMessage()
                );

                $this->logger->error($message);

                throw new TranslationFailedException($message, $e->getCode(), $e);
            }

            $waitTime = $waitSeconds * $attemptNumber;
            $this->logger->warning(
                sprintf('Rate limit hit in %s. Will retry after %d seconds.', get_class($this), $waitTime)
            );

            sleep($waitTime);

            return $this->attemptTranslation($value, ++$attemptNumber);
        }
    }
}
