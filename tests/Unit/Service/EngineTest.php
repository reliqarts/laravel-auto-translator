<?php

/**
 * @noinspection PhpParamsInspection
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpStrictTypeCheckingInspection
 * @noinspection PhpVoidFunctionResultUsedInspection
 */

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Unit\Service;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\AutoTranslator\Contract\ConfigProvider;
use ReliqArts\AutoTranslator\Contract\Translator;
use ReliqArts\AutoTranslator\Exception\AutoTranslationFailedException;
use ReliqArts\AutoTranslator\Exception\TranslationFailedException;
use ReliqArts\AutoTranslator\Model\LanguageCode;
use ReliqArts\AutoTranslator\Model\Translation;
use ReliqArts\AutoTranslator\Service;
use ReliqArts\AutoTranslator\Service\LanguageFileManager;
use ReliqArts\AutoTranslator\Service\TranslatableStringExporter;
use ReliqArts\AutoTranslator\Service\TranslatorProvider;
use ReliqArts\AutoTranslator\Tests\Unit\TestCase;
use ReliqArts\Contract\Logger;
use RuntimeException;

final class EngineTest extends TestCase
{
    private const BASE_LANGUAGE = 'en';

    private const KEY_NEW_TRANSLATIONS = 'new_translations';

    private const KEY_TRANSLATED_STRINGS_COUNT = 'translated_strings_count';

    private const VAR_PSEUDO_PLACEHOLDER = '_-_';

    private ObjectProphecy|ConfigProvider $configProvider;

    private ObjectProphecy|Logger $logger;

    private ObjectProphecy|LanguageFileManager $languageFileManager;

    private ObjectProphecy|TranslatorProvider $translatorProvider;

    private ObjectProphecy|Translator $translator;

    private Service $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider = $this->prophesize(ConfigProvider::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->languageFileManager = $this->prophesize(LanguageFileManager::class);
        $this->translator = $this->prophesize(Translator::class);

        $this->configProvider->get(ConfigProvider::KEY_BASE_LANGUAGE)
            ->willReturn('en');

        $this->translatorProvider = $this->prophesize(TranslatorProvider::class);
        $this->translatorProvider->get()
            ->willReturn($this->translator->reveal());

        $translatableStringExporter = $this->prophesize(TranslatableStringExporter::class);

        $this->subject = new Service\Engine(
            $this->configProvider->reveal(),
            $this->logger->reveal(),
            $translatableStringExporter->reveal(),
            $this->translatorProvider->reveal(),
            $this->languageFileManager->reveal()
        );
    }

    /**
     * @throws Exception
     */
    public function test_translate(): void
    {
        $language = LanguageCode::from('es');
        $alreadyTranslated = [self::FOO => 'bar'];
        $originalText = 'Hello there. My name is :name. Are you :your_name?';
        $expectedTranslationKey = sprintf(
            'Hello there. My name is %s. Are you %s?',
            self::VAR_PSEUDO_PLACEHOLDER,
            self::VAR_PSEUDO_PLACEHOLDER
        );
        $expectedTranslatorResult = new Translation(
            sprintf('Hola. Mi nombre es %s. Eres %s?', self::VAR_PSEUDO_PLACEHOLDER, self::VAR_PSEUDO_PLACEHOLDER),
            $language,
            $originalText,
            LanguageCode::from(self::BASE_LANGUAGE)
        );
        $expectedTranslatedText = 'Hola. Mi nombre es :name. Eres :your_name?';

        $this->languageFileManager->read($language)
            ->shouldBeCalledOnce()
            ->willReturn($alreadyTranslated + [$originalText => $originalText]);

        $this->translator->translate($expectedTranslationKey, $language, Argument::type(LanguageCode::class))
            ->shouldBeCalledOnce()
            ->willReturn($expectedTranslatorResult);

        $this->languageFileManager->write($language, $alreadyTranslated + [$originalText => $expectedTranslatedText])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->logger->error(Argument::cetera())
            ->shouldNotBeCalled();

        $result = $this->subject->translate($language);

        self::assertTrue($result->isSuccess());
        self::assertArrayHasKey(self::KEY_NEW_TRANSLATIONS, $result->getExtra());
    }

    /**
     * @throws Exception
     */
    public function test_translate_when_translator_not_configured(): void
    {
        $expectedExceptionMessage = 'A configuration error occurred';

        $this->translatorProvider->get()
            ->willThrow(new BindingResolutionException('Not available'));

        $this->languageFileManager->read(Argument::cetera())
            ->shouldNotBeCalled();

        $this->translator->translate(Argument::cetera())
            ->shouldNotBeCalled();

        $this->languageFileManager->write(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logger->error(
            Argument::that(
                static fn (string $message) => Str::startsWith($message, $expectedExceptionMessage)
            ),
            Argument::type('array')
        )->shouldBeCalledOnce();

        $result = $this->subject->translate('es');

        self::assertFalse($result->isSuccess());
        self::assertTrue(Str::startsWith($result->getError(), $expectedExceptionMessage));
    }

    /**
     * @throws Exception
     */
    public function test_translate_when_language_error_occurs(): void
    {
        $expectedExceptionMessage = 'Failed to read/write `es`';

        $this->languageFileManager->read(Argument::type(LanguageCode::class))
            ->shouldBeCalledOnce()
            ->willThrow(new FileNotFoundException('Language file does not exist.'));

        $this->translator->translate(Argument::cetera())
            ->shouldNotBeCalled();

        $this->languageFileManager->write(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logger->error(
            Argument::that(
                static fn (string $message) => Str::startsWith($message, $expectedExceptionMessage)
            ),
            Argument::type('array')
        )->shouldBeCalledOnce();

        $result = $this->subject->translate('es');

        self::assertFalse($result->isSuccess());
        self::assertTrue(Str::startsWith($result->getError(), $expectedExceptionMessage));
    }

    /**
     * @throws Exception
     */
    public function test_translate_when_translator_translate_fails(): void
    {
        $language = LanguageCode::from('es');
        $originalText = 'Hello. Are you :your_name?';
        $expectedTranslatorResult = new Translation(
            sprintf('Hola. Eres %s?', self::VAR_PSEUDO_PLACEHOLDER),
            $language,
            $originalText,
            LanguageCode::from(self::BASE_LANGUAGE)
        );
        $expectedTranslatedText = 'Hola. Eres :your_name?';

        $this->languageFileManager->read($language)
            ->shouldBeCalledOnce()
            ->willReturn([self::FOO => self::FOO, $originalText => $originalText]);

        $this->translator->translate(self::FOO, $language, Argument::type(LanguageCode::class))
            ->shouldBeCalledOnce()
            ->willThrow(new TranslationFailedException('failed'));
        $this->translator->translate(
            sprintf('Hello. Are you %s?', self::VAR_PSEUDO_PLACEHOLDER),
            $language,
            Argument::type(LanguageCode::class)
        )
            ->shouldBeCalledOnce()
            ->willReturn($expectedTranslatorResult);

        $this->languageFileManager->write($language, [self::FOO => self::FOO, $originalText => $expectedTranslatedText])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->logger->error(
            Argument::that(
                static fn (string $message) => Str::startsWith($message, 'Translation failed')
            )
        )->shouldBeCalledOnce();

        $result = $this->subject->translate($language);

        self::assertTrue($result->isSuccess());
        self::assertArrayHasKey(self::KEY_NEW_TRANSLATIONS, $result->getExtra());
        self::assertCount(1, $result->getExtra()[self::KEY_NEW_TRANSLATIONS]);
    }

    /**
     * @throws Exception
     */
    public function test_translate_all(): void
    {
        $targetLanguages = ['es', 'not-valid', 'fr'];
        $originalTranslationsMap = ['hello' => 'hello'];

        $this->configProvider->get(ConfigProvider::KEY_TARGET_LANGUAGES)
            ->shouldBeCalledOnce()
            ->willReturn($targetLanguages);

        $this->languageFileManager->read(Argument::that(static fn (LanguageCode $arg) => (string) $arg === 'es'))
            ->shouldBeCalledOnce()
            ->willReturn($originalTranslationsMap);
        $this->languageFileManager->read(Argument::that(static fn (LanguageCode $arg) => (string) $arg === 'fr'))
            ->shouldBeCalledOnce()
            ->willReturn($originalTranslationsMap);

        $this->translator->translate(Argument::cetera())
            ->shouldBeCalledTimes(2)
            ->willReturn(
                new Translation(
                    'hola',
                    LanguageCode::from('es'),
                    'hello',
                    LanguageCode::from(self::BASE_LANGUAGE)
                ),
                new Translation(
                    'bonjour',
                    LanguageCode::from('fr'),
                    'hello',
                    LanguageCode::from(self::BASE_LANGUAGE)
                )
            );

        $this->languageFileManager->write(
            Argument::that(static fn (LanguageCode $arg) => (string) $arg === 'es'),
            ['hello' => 'hola']
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->languageFileManager->write(
            Argument::that(static fn (LanguageCode $arg) => (string) $arg === 'fr'),
            ['hello' => 'bonjour']
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->logger->error(Argument::cetera())
            ->shouldNotBeCalled();
        $this->logger->warning(Argument::that(static fn (string $arg) => Str::contains($arg, '`not-valid` is invalid')))
            ->shouldBeCalledOnce();

        $result = $this->subject->translateAll();

        self::assertTrue($result->isSuccess());
        self::assertArrayHasKey(self::KEY_TRANSLATED_STRINGS_COUNT, $result->getExtra());
        self::assertEquals(2, $result->getExtra()[self::KEY_TRANSLATED_STRINGS_COUNT]);
    }

    /**
     * @throws Exception
     */
    public function test_translate_all_when_translation_fails(): void
    {
        $this->configProvider->get(ConfigProvider::KEY_TARGET_LANGUAGES)
            ->shouldBeCalledOnce()
            ->willReturn(['es']);

        $this->languageFileManager->read(Argument::that(static fn (LanguageCode $arg) => (string) $arg === 'es'))
            ->shouldBeCalledOnce()
            ->willThrow(new FileNotFoundException('fnf'));

        $this->translator->translate(Argument::cetera())
            ->shouldNotBeCalled();

        $this->languageFileManager->write(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logger->error(Argument::that(static fn (string $arg) => Str::contains($arg, 'read/write `es`')), Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->logger->warning(Argument::cetera())
            ->shouldNotBeCalled();

        $result = $this->subject->translateAll();

        self::assertFalse($result->isSuccess());
        self::assertArrayHasKey(self::KEY_TRANSLATED_STRINGS_COUNT, $result->getExtra());
        self::assertEquals(0, $result->getExtra()[self::KEY_TRANSLATED_STRINGS_COUNT]);
        self::assertStringContainsStringIgnoringCase('0/1 languages were translated', $result->getError());
        self::assertStringContainsStringIgnoringCase('failed to read/write `es` language file', $result->getMessages()[0]);
    }

    /**
     * @throws Exception
     */
    public function test_translate_all_when_critical_failure_occurs(): void
    {
        $exceptionMessage = 'oops';

        $this->configProvider->get(ConfigProvider::KEY_TARGET_LANGUAGES)
            ->shouldBeCalledOnce()
            ->willThrow(new RuntimeException($exceptionMessage));

        $this->languageFileManager->read(Argument::cetera())
            ->shouldNotBeCalled();

        $this->translator->translate(Argument::cetera())
            ->shouldNotBeCalled();

        $this->languageFileManager->write(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logger->error(Argument::that(static fn (string $arg) => Str::contains($arg, $exceptionMessage)))
            ->shouldBeCalledOnce();
        $this->logger->warning(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(AutoTranslationFailedException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->subject->translateAll();
    }
}
