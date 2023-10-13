<?php

/** @noinspection PhpVoidFunctionResultUsedInspection */

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Unit\Service;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\AutoTranslator\Model\LanguageCode;
use ReliqArts\AutoTranslator\Service\LanguageFileManager;
use ReliqArts\AutoTranslator\Tests\Unit\TestCase;
use ReliqArts\Service\Filesystem;

final class LanguageFileManagerTest extends TestCase
{
    private ObjectProphecy|Application $app;

    private ObjectProphecy|Filesystem $filesystem;

    private LanguageFileManager $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->prophesize(Application::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->subject = new LanguageFileManager($this->app->reveal(), $this->filesystem->reveal());
    }

    /**
     * @throws Exception
     */
    public function testWrite(): void
    {
        $lang = LanguageCode::from('es');
        $filePath = '/lang/es.json';
        $bytesWritten = 350;
        $mappings = [
            'hello' => 'hola',
            'where are you?' => 'dondé estás?',
        ];

        $this->app->langPath("$lang.json")
            ->willReturn($filePath);

        $this->filesystem->replace(
            $filePath,
            json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        )
            ->shouldBeCalledOnce()
            ->willReturn($bytesWritten);

        self::assertTrue($this->subject->write($lang, $mappings));
    }

    /**
     * @throws Exception
     */
    public function testRead(): void
    {
        $lang = LanguageCode::from('fr');
        $filePath = '/lang/fr.json';

        $this->app->langPath("$lang.json")
            ->willReturn($filePath);

        $this->filesystem->get($filePath)
            ->shouldBeCalledOnce()
            ->willReturn('{"hello": "hola", "where are you?": "dondé estás?"}');

        $result = $this->subject->read($lang);

        self::assertSame('hola', $result['hello']);
        self::assertSame('dondé estás?', $result['where are you?']);
    }
}
