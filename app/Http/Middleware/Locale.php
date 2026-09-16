<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;

class Locale
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        $raw_locale = $request->cookie('lang')
            ?: $this->detectLocaleFromBrowser((string) $request->header('Accept-Language'));

        if (in_array($raw_locale, config('app.locales', []), true)) {
            $locale = $raw_locale;
        } elseif (in_array(config('app.locale'), config('app.locales', []), true)) {
            $locale = config('app.locale');
        } else {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Detect the best supported locale from the browser Accept-Language header.
     *
     * @param string $acceptLanguage
     * @return string|null
     */
    private function detectLocaleFromBrowser(string $acceptLanguage): ?string
    {
        if ($acceptLanguage === '') {
            return null;
        }

        $locales = config('app.locales', []);
        $acceptedLocales = [];

        foreach (explode(',', $acceptLanguage) as $position => $language) {
            if (!preg_match('/^\s*([a-zA-Z-]+)(?:\s*;\s*q=([0-9.]+))?\s*$/', $language, $matches)) {
                continue;
            }

            $quality = isset($matches[2]) ? (float) $matches[2] : 1.0;

            if ($quality <= 0) {
                continue;
            }

            $acceptedLocales[] = [
                'locale' => strtolower($matches[1]),
                'quality' => $quality,
                'position' => $position,
            ];
        }

        usort($acceptedLocales, fn (array $left, array $right): int => $right['quality'] <=> $left['quality']
            ?: $left['position'] <=> $right['position']);

        foreach ($acceptedLocales as $acceptedLocale) {
            $matchedLocale = $this->matchSupportedLocale($acceptedLocale['locale'], $locales);

            if ($matchedLocale !== null) {
                return $matchedLocale;
            }
        }

        return null;
    }

    /**
     * Match an accepted browser locale against the supported application locales.
     *
     * @param string $locale
     * @param array $locales
     * @return string|null
     */
    private function matchSupportedLocale(string $locale, array $locales): ?string
    {
        if (in_array($locale, $locales, true)) {
            return $locale;
        }

        $language = substr($locale, 0, 2);

        foreach ($locales as $availableLocale) {
            if ($language === substr(strtolower($availableLocale), 0, 2)) {
                return $availableLocale;
            }
        }

        return null;
    }
}
