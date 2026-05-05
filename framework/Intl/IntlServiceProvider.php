<?php

declare(strict_types=1);

namespace Framework\Intl;

use Framework\Foundation\ServiceProvider;

class IntlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $locale = config('app.locale', 'en');

        if (isset($_COOKIE['locale'])) {
            $cookieLocale = $_COOKIE['locale'];
            $supported = config('intl.locales', ['en', 'zh']);
            if (in_array($cookieLocale, $supported, true)) {
                $locale = $cookieLocale;
            }
        }

        Translator::init(
            basePath: config('intl.path', base_path('resources/lang')),
            locale: $locale,
            fallback: config('app.fallback_locale', 'en')
        );
    }
}
