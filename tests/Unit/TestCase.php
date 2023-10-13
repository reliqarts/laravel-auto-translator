<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

abstract class TestCase extends BaseTestCase
{
    protected const FOO = 'foo';

    use ProphecyTrait;
}
