<?php

declare(strict_types=1);

use ReliqArts\AutoTranslator\Service\Translator;

return [
    /**
     * Base language code - ISO-639
     *
     * @see https://en.m.wikipedia.org/wiki/ISO_639
     */
    'base_language' => env(
        'AUTO_TRANSLATOR_BASE_LANGUAGE_CODE',
        config('app.locale', config('app.fallback_locale', 'en'))
    ),

    /**
     * Languages to auto-translate to. (ISO-639)
     */
    'languages' => [
        'de',
        'es',
        'fr',
    ],

    'auto_translate_via' => Translator\SimpleGoogleApiTranslator::class,

    /**
     * Translator specific configuration keyed by translator slug.
     */
    'translators' => [
        'simple_google_api_translator' => [
            /**
             * Translator will wait for {wait_seconds} seconds before making another request after receiving a
             * rate limit exception. This wait time increases with the number of attempts such that the wait time for the
             * second attempt is {wait_seconds}*2.
             */
            'wait_seconds' => 2,

            /**
             * Max attempts to make per request.
             */
            'max_attempts' => 3,
        ],
        'google_cloud_translator' => [
            'api_key' => '',
        ],
        'deepl_translator' => [
            'api_key' => '',
        ],
    ],

    /**
     * Language switcher specific configuration.
     */
    'language_switcher' => [
        /**
         * Language switcher endpoint.
         */
        'endpoint' => 'lang/switch',

        /**
         * Specify the name of the language switcher route.
         */
        'route_name' => 'switch-language',

        /**
         * Specify bindings such as middleware for the language switcher route.
         */
        'route_group_attributes' => [
            'middleware' => ['web'],
            // 'as' => 'lang.',
        ],

        /**
         * Message returned from endpoint when language was switched. Set to 'null' to disable.
         */
        'confirmation_text' => 'Language switched.',
    ],
];
