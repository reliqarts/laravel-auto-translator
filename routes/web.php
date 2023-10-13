<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use ReliqArts\AutoTranslator\Contract\ConfigProvider;
use ReliqArts\AutoTranslator\Http\Controller\LanguageController;

/**
 * @var ConfigProvider $configProvider
 */
$configProvider = resolve(ConfigProvider::class);
$languageSwitcherConfig = $configProvider->get(ConfigProvider::KEY_LANGUAGE_SWITCHER);

Route::group($languageSwitcherConfig['route_group_attributes'], static function () use ($languageSwitcherConfig) {
    Route::post($languageSwitcherConfig['endpoint'], [LanguageController::class, 'switchLanguage'])
        ->name($languageSwitcherConfig['route_name']);
});
