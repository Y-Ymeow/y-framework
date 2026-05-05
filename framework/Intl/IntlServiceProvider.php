<?php

declare(strict_types=1);

namespace Framework\Intl;

use Framework\Foundation\ServiceProvider;

class IntlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Translator::init(
            basePath: config('intl.path', base_path('resources/lang')),
            locale: config('app.locale', 'en'),
            fallback: config('app.fallback_locale', 'en')
        );
    }
}
