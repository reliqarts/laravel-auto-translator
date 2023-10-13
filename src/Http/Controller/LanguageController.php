<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ReliqArts\AutoTranslator\Contract\ConfigProvider;
use RuntimeException;

class LanguageController
{
    private const KEY_LANG = 'lang';

    /**
     * @throws RuntimeException
     */
    public function switchLanguage(ConfigProvider $configProvider, Request $request): RedirectResponse
    {
        $lang = $request->get(self::KEY_LANG);
        if ($lang === null) {
            Log::error('Language switching failed. No language (locale) provided.');

            return redirect()->back();
        }

        $request->session()->put(self::KEY_LANG, $lang);

        Log::info(sprintf('Language updated to %s', $lang));

        $confirmationText = $configProvider->get(ConfigProvider::KEY_LANGUAGE_SWITCHER)['confirmation_text'] ?? null;

        return $confirmationText === null
            ? redirect()->back()
            : redirect()->back()
                ->with('message', $confirmationText);
    }
}
