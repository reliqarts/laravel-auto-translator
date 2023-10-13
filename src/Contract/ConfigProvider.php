<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Contract;

use ReliqArts\Contract\ConfigProvider as BaseConfigProvider;

interface ConfigProvider extends BaseConfigProvider
{
    public const KEY_API_KEY = 'api_key';

    public const KEY_BASE_LANGUAGE = 'base_language';

    public const KEY_TARGET_LANGUAGES = 'languages';

    public const KEY_AUTO_TRANSLATE_VIA = 'auto_translate_via';

    public const KEY_LANGUAGE_SWITCHER = 'language_switcher';

    public const KEY_TRANSLATORS = 'translators';
}
