<?php

declare(strict_types=1);

namespace Framework\Intl;

class LocaleMiddleware
{
    public function handle(\Framework\Http\Request $request, \Closure $next): mixed
    {
        $locale = $request->input('_locale')
            ?? $request->header('Accept-Language', config('app.locale', 'en'));

        if (is_string($locale)) {
            $locale = substr($locale, 0, 2);
            if (in_array($locale, config('app.locales', ['en', 'zh', 'fr', 'es', 'de']), true)) {
                Translator::setLocale($locale);
                setlocale(LC_ALL, $locale . '_' . strtoupper($locale) . '.UTF-8');
            }
        }

        return $next($request);
    }
}
