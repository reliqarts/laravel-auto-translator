<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Console\Command;

use Illuminate\Console\Command;
use ReliqArts\AutoTranslator\Exception\AutoTranslationFailedException;
use ReliqArts\AutoTranslator\Service;

final class AutoTranslate extends Command
{
    /**
     * @var string
     */
    protected $signature = 'auto-translator:translate
                            {lang? : Language to translate in ISO-639 format (e.g. es,en - defaults to all)}
                            {--r|replace-existing : Whether existing translations should be replaced}';

    /**
     * @var string
     */
    protected $description = 'Automatically generate translations from your base language to all configured languages.';

    /**
     * Execute
     */
    public function handle(Service $autoTranslator): void
    {
        try {
            $lang = $this->argument('lang');
            $replaceExisting = $this->option('replace-existing');
            $targetLanguages = $lang !== null ? array_filter(explode(',', $lang)) : [];

            $progressBar = $this->getOutput()->createProgressBar(
                empty($targetLanguages) ? 100 : count($targetLanguages)
            );

            $progressBar->start();
            $result = $autoTranslator->translateMany($targetLanguages, $replaceExisting);
            $progressBar->finish();

            $this->newLine();

            if (! $result->isSuccess()) {
                $this->comment(sprintf('<error>✘</error> %s', $result->getError()));
                $this->table(['Messages'], array_map(static fn ($val) => [$val], $result->getMessages()));

                return;
            }

            $this->info(sprintf('<comment>✔</comment> %s', $result->getMessage()));
        } catch (AutoTranslationFailedException $e) {
            $this->comment(sprintf('<error>✘</error> %s', $e->getMessage()));
        }
    }
}
