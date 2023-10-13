<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use KKomelin\TranslatableStringExporter\Core\Exporter;
use LogicException;
use ReliqArts\AutoTranslator\Console\Command\AutoTranslate;
use ReliqArts\AutoTranslator\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\AutoTranslator\Contract\Translator;
use ReliqArts\AutoTranslator\Service\ConfigProvider;
use ReliqArts\AutoTranslator\Service\Engine;
use ReliqArts\AutoTranslator\Service\LanguageFileManager;
use ReliqArts\AutoTranslator\Service\TranslatableStringExporter;
use ReliqArts\AutoTranslator\Service\Translator\DeepLTranslator;
use ReliqArts\AutoTranslator\Service\Translator\SimpleGoogleApiTranslator;
use ReliqArts\AutoTranslator\Service\TranslatorProvider;
use ReliqArts\Contract\Logger;
use ReliqArts\Contract\LoggerFactory;
use ReliqArts\Service\ConfigProvider as ReliqArtsConfigProvider;
use Stichoza\GoogleTranslate\GoogleTranslate;

final class ServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * Register package services.
     *
     * @throws LogicException
     */
    public function register(): void
    {
        $loggerName = $this->getLoggerName();

        $this->app->singleton(
            $loggerName,
            fn (Application $app): Logger => $app->make(LoggerFactory::class)
                ->create($loggerName, $this->getConfigKey())
        );
        $this->app->singleton(
            ConfigProviderContract::class,
            fn (Application $app): ConfigProviderContract => new ConfigProvider(
                new ReliqArtsConfigProvider($app->make(ConfigRepository::class), $this->getConfigKey())
            )
        );
        $this->app->singleton(
            SimpleGoogleApiTranslator::class,
            static fn (Application $app): Translator => new SimpleGoogleApiTranslator(
                $app->make(ConfigProviderContract::class),
                $app->make($loggerName),
                $app->make(GoogleTranslate::class),
            )
        );
        $this->app->singleton(
            DeepLTranslator::class,
            static fn (Application $app): Translator => new DeepLTranslator(
                $app->make(ConfigProviderContract::class),
                $app->make($loggerName)
            )
        );
        $this->app->singleton(
            Service::class,
            fn (Application $app) => new Engine(
                $app->make(ConfigProviderContract::class),
                $app->make($loggerName),
                new TranslatableStringExporter($app->make(Exporter::class)),
                $app->make(TranslatorProvider::class),
                $app->make(LanguageFileManager::class)
            )
        );
    }

    /**
     * Bootstrap the package.
     */
    public function boot(): void
    {
        $this->bootstrapConfig();
        $this->bootstrapCommands();
        $this->bootstrapRoutes();
    }

    /**
     * @codeCoverageIgnore
     */
    public function provides(): array
    {
        return [
            $this->getLoggerName(),
            ConfigProviderContract::class,
            SimpleGoogleApiTranslator::class,
            Service::class,
        ];
    }

    private function bootstrapConfig(): void
    {
        $configKey = $this->getConfigKey();
        $configFile = sprintf('%s/config/config.php', $this->getRootDir());

        $this->mergeConfigFrom($configFile, $configKey);
        $this->publishes(
            [$configFile => config_path(sprintf('%s.php', $configKey))],
            sprintf('%s-config', $configKey)
        );
    }

    private function bootstrapCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // Register the commands...
            $this->commands([
                AutoTranslate::class,
            ]);
        }
    }

    private function bootstrapRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    private function getRootDir(): string
    {
        return __DIR__.'/..';
    }

    private function getConfigKey(): string
    {
        return 'auto-translator';
    }

    private function getLoggerName(): string
    {
        return sprintf('%s.logger', $this->getConfigKey());
    }
}
