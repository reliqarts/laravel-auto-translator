<?php
/**
 * @noinspection PhpStrictTypeCheckingInspection
 * @noinspection PhpVoidFunctionResultUsedInspection
 */

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Unit\Service\Translator;

use Exception;
use Illuminate\Support\Str;
use Prophecy\Argument;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Prophecy\ObjectProphecyException;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\AutoTranslator\Contract\ConfigProvider;
use ReliqArts\AutoTranslator\Contract\Translator;
use ReliqArts\AutoTranslator\Exception\TranslationFailedException;
use ReliqArts\AutoTranslator\Service\Translator\SimpleGoogleApiTranslator;
use ReliqArts\AutoTranslator\Tests\Unit\TestCase;
use ReliqArts\Contract\Logger;
use Stichoza\GoogleTranslate\Exceptions\RateLimitException;
use Stichoza\GoogleTranslate\GoogleTranslate;

final class SimpleGoogleApiTranslatorTest extends TestCase
{
    private const CONFIG_KEY = 'translators'.'.simple_google_api_translator';

    private ObjectProphecy|ConfigProvider $configProvider;

    private ObjectProphecy|Logger $logger;

    private ObjectProphecy|GoogleTranslate $translator;

    private Translator $subject;

    /**
     * @throws ObjectProphecyException
     * @throws DoubleException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider = $this->prophesize(ConfigProvider::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->translator = $this->prophesize(GoogleTranslate::class);

        $this->translator->setTarget(Argument::any())
            ->willReturn($this->translator->reveal());
        $this->translator->setSource(Argument::any())
            ->willReturn($this->translator->reveal());

        $this->subject = new SimpleGoogleApiTranslator(
            $this->configProvider->reveal(),
            $this->logger->reveal(),
            $this->translator->reveal()
        );
    }

    /**
     * @medium
     *
     * @throws Exception
     */
    public function testTranslate(): void
    {
        $input = 'My string';
        $expectedTranslation = 'My string translated';
        $translatorCallIndex = 0;

        $this->configProvider->get(self::CONFIG_KEY, [])
            ->shouldBeCalledTimes(2)
            ->willReturn([]);

        $this->translator->translate($input)
            ->shouldBeCalledTimes(2)
            ->will(function () use ($expectedTranslation, &$translatorCallIndex) {
                if ($translatorCallIndex > 0) {
                    return $expectedTranslation;
                }

                // throw an exception the first time
                $translatorCallIndex++;
                throw new RateLimitException('rate limit hit');
            });
        $this->translator->getLastDetectedSource()
            ->shouldBeCalledOnce()
            ->willReturn('en');

        $this->logger->error(Argument::cetera())
            ->shouldNotBeCalled();
        $this->logger->warning(Argument::that(static fn (string $arg) => Str::contains($arg, 'Rate limit hit')))
            ->shouldBeCalledTimes(1);

        $result = $this->subject->translate($input, 'fr');

        self::assertSame($expectedTranslation, $result->getValue());
    }

    /**
     * @large
     *
     * @throws Exception
     */
    public function testTranslateWhenExceptionOccurs(): void
    {
        $input = 'A sentence.';

        $this->configProvider->get(self::CONFIG_KEY, [])
            ->shouldBeCalledTimes(3)
            ->willReturn([]);

        $this->translator->translate($input)
            ->shouldBeCalledTimes(3)
            ->willthrow(new RateLimitException('limit'));
        $this->translator->getLastDetectedSource()
            ->shouldNotBeCalled();

        $this->logger->error(
            Argument::that(
                static fn (string $arg) => Str::contains($arg, 'Max attempts (3) exhausted')
            )
        )->shouldBeCalledOnce();
        $this->logger->warning(Argument::that(static fn (string $arg) => Str::contains($arg, 'Rate limit hit')))
            ->shouldBeCalledTimes(2);

        $this->expectException(TranslationFailedException::class);
        $this->expectExceptionMessage('Max attempts (3) exhausted');

        $this->subject->translate($input, 'fr');
    }
}
