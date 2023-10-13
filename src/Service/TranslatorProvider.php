<?php

/** @noinspection PhpClassCanBeReadonlyInspection */

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use ReliqArts\AutoTranslator\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\AutoTranslator\Contract\Translator as TranslatorContract;
use ReliqArts\AutoTranslator\Service\Translator\SimpleGoogleApiTranslator;

/**
 * @internal
 */
class TranslatorProvider
{
    public function __construct(
        private readonly ConfigProviderContract $configProvider,
        private readonly Application $app
    ) {
    }

    /**
     * Get the configured translator or default Google API Translator.
     *
     * @throws BindingResolutionException
     */
    public function get(): TranslatorContract
    {
        $className = $this->configProvider->get(
            ConfigProviderContract::KEY_AUTO_TRANSLATE_VIA,
            SimpleGoogleApiTranslator::class
        );

        return $this->app->make($className);
    }
}
