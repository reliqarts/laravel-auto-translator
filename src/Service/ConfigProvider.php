<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Service;

use ReliqArts\AutoTranslator\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\Contract\ConfigProvider as ReliqArtsConfigProvider;

/**
 * @codeCoverageIgnore
 */
final readonly class ConfigProvider implements ConfigProviderContract
{
    public function __construct(private ReliqArtsConfigProvider $provider)
    {
    }

    public function get(?string $key, $default = null): mixed
    {
        return $this->provider->get($key, $default);
    }
}
