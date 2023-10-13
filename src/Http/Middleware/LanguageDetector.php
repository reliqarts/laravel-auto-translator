<?php

declare(strict_types=1);

namespace ReliqArts\AutoTranslator\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use RuntimeException;

readonly class LanguageDetector
{
    public function __construct(private Application $app)
    {
    }

    /**
     * @throws RuntimeException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (($lang = session('lang')) && $this->app->getLocale() !== $lang) {
            $this->app->setLocale($lang);
        }

        return $next($request);
    }
}
