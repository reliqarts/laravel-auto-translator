<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Tests\Integration\Http\Controller;

use ReliqArts\AutoTranslator\Tests\Integration\TestCase;

final class LanguageControllerTest extends TestCase
{
    private const LANGUAGE_SWITCHER_ROUTE_NAME = 'switch-language';

    private const REFERRER_URL = 'http://ref';

    public function testSwitchLanguage(): void
    {
        $this->from(self::REFERRER_URL)
            ->post(route(self::LANGUAGE_SWITCHER_ROUTE_NAME), ['lang' => 'es'])
            ->assertRedirect(self::REFERRER_URL)
            ->assertSessionHas('lang', 'es');
    }

    public function testSwitchLanguageWhenLangNotProvided(): void
    {
        $this->from(self::REFERRER_URL)
            ->post(route(self::LANGUAGE_SWITCHER_ROUTE_NAME))
            ->assertRedirect(self::REFERRER_URL)
            ->assertSessionMissing('lang');
    }

    public function testSwitchLanguageWhenMethodNotAllowed(): void
    {
        $response = $this->get(route(self::LANGUAGE_SWITCHER_ROUTE_NAME, ['lang' => 'fr']));

        $response->assertMethodNotAllowed();
    }
}
